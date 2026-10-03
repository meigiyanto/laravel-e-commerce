<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Payment;
use App\Models\Refund;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RefundService
{
    public function __construct(
        protected StripePaymentService $stripe,
        protected MidtransService $midtrans,
    ) {}

    /**
     * Mengajukan refund berdasarkan payment yang sudah berhasil.
     */
    public function request(
        Order $order,
        int|float|string $amount,
        ?string $reason = null
    ): Refund {
        return DB::transaction(function () use (
            $order,
            $amount,
            $reason
        ) {
            $payment = $order->payment()
                ->lockForUpdate()
                ->first();

            if (! $payment) {
                throw ValidationException::withMessages([
                    'payment' => 'Order belum memiliki pembayaran.',
                ]);
            }

            if ($payment->status !== 'succeeded') {
                throw ValidationException::withMessages([
                    'payment' => 'Pembayaran belum berhasil sehingga belum dapat direfund.',
                ]);
            }

            $amount = (float) $amount;

            if ($amount <= 0) {
                throw ValidationException::withMessages([
                    'amount' => 'Nominal refund harus lebih besar dari 0.',
                ]);
            }

            $refundableAmount = $this->refundableAmount($payment);

            if ($amount > $refundableAmount) {
                throw ValidationException::withMessages([
                    'amount' => 'Nominal refund melebihi sisa dana yang dapat direfund.',
                ]);
            }

            return Refund::create([
                'order_id' => $order->id,
                'payment_id' => $payment->id,
                'amount' => $amount,
                'currency' => $payment->currency ?? 'IDR',
                'reason' => $reason,
                'status' => Refund::STATUS_REQUESTED,
                'provider' => $payment->provider,
                'requested_at' => now(),
            ]);
        });
    }

    /**
     * Menghitung sisa nominal yang masih dapat direfund.
     */
    public function refundableAmount(Payment $payment): float
    {
        $refundedAmount = (float) $payment->refunds()
            ->whereIn('status', [
                Refund::STATUS_REQUESTED,
                Refund::STATUS_APPROVED,
                Refund::STATUS_PROCESSING,
                Refund::STATUS_COMPLETED,
            ])
            ->sum('amount');

        return max(
            0,
            (float) $payment->gross_amount - $refundedAmount
        );
    }

    /**
     * Memproses refund yang masih berstatus requested.
     *
     * Transaction boundary:
     *
     * 1. Lock Payment terlebih dahulu.
     * 2. Lock Refund setelah Payment.
     * 3. Validasi seluruh state di dalam transaction.
     * 4. Claim refund dengan mengubahnya menjadi processing.
     * 5. Commit transaction.
     * 6. Baru setelah commit, panggil provider.
     *
     * Payment menjadi serialization point untuk seluruh refund
     * yang berasal dari payment yang sama.
     */
    public function process(Refund $refund): Refund
    {
        /*
        * payment_id merupakan foreign key yang immutable untuk refund.
        *
        * Kita hanya membaca refund terlebih dahulu untuk mengetahui
        * Payment mana yang harus menjadi lock pertama.
        *
        * Lock belum dilakukan di sini.
        */
        $refundData = Refund::query()
            ->select([
                'id',
                'payment_id',
            ])
            ->findOrFail($refund->id);

        $refund = DB::transaction(function () use ($refundData) {
            /*
            * ============================================================
            * LOCK ORDER
            * ============================================================
            *
            * Seluruh alur refund harus menggunakan urutan:
            *
            * Payment -> Refund
            *
            * Jangan mengubah urutan ini menjadi:
            *
            * Refund -> Payment
            *
            * karena request() juga mengunci Payment terlebih dahulu.
            */

            $payment = Payment::query()
                ->whereKey($refundData->payment_id)
                ->lockForUpdate()
                ->first();

            if (! $payment) {
                throw ValidationException::withMessages([
                    'payment' => 'Payment untuk refund tidak ditemukan.',
                ]);
            }

            /*
            * Setelah Payment terkunci, baru lock Refund.
            *
            * Dengan demikian dua proses refund terhadap Payment yang sama
            * akan diserialisasi oleh row Payment.
            */
            $refund = Refund::query()
                ->whereKey($refundData->id)
                ->lockForUpdate()
                ->first();

            if (! $refund) {
                throw ValidationException::withMessages([
                    'refund' => 'Refund tidak ditemukan.',
                ]);
            }

            /*
            * Hanya refund REQUESTED yang boleh di-claim.
            *
            * Jika worker/admin kedua datang bersamaan:
            *
            * Request A:
            *   Payment locked
            *   Refund locked
            *   status -> processing
            *   commit
            *
            * Request B:
            *   menunggu Payment
            *   setelah Payment dilepas:
            *   membaca Refund = processing
            *   ditolak
            *
            * Dengan demikian provider tidak dipanggil dua kali.
            */
            if ($refund->status !== Refund::STATUS_REQUESTED) {
                throw ValidationException::withMessages([
                    'refund' => 'Refund ini sudah diproses atau tidak dapat diproses kembali.',
                ]);
            }

            /*
            * Payment harus tetap succeeded ketika refund di-claim.
            */
            if ($payment->status !== 'succeeded') {
                throw ValidationException::withMessages([
                    'payment' => 'Payment belum berada pada status succeeded.',
                ]);
            }

            /*
            * Karena Payment sedang di-lock, tidak ada request()
            * lain yang dapat membuat refund baru untuk Payment ini
            * sampai transaction selesai.
            *
            * Refund dengan status REQUESTED / APPROVED / PROCESSING /
            * COMPLETED ikut dihitung sebagai dana yang sudah dicadangkan
            * atau sudah direfund.
            */
            $refundableAmount = $this->refundableAmount($payment);

            if ((float) $refund->amount > $refundableAmount) {
                throw ValidationException::withMessages([
                    'amount' => 'Nominal refund melebihi sisa dana yang dapat direfund.',
                ]);
            }

            /*
            * Provider harus divalidasi SEBELUM refund di-claim.
            *
            * Kalau provider tidak didukung:
            *
            * requested -> tetap requested
            *
            * bukan:
            *
            * requested -> processing -> failed
            */
            if (! in_array(
                $payment->provider,
                ['stripe', 'midtrans', 'cod'],
                true
            )) {
                throw ValidationException::withMessages([
                    'provider' => "Provider pembayaran [{$payment->provider}] belum didukung untuk refund.",
                ]);
            }

            /*
            * ============================================================
            * ATOMIC CLAIM
            * ============================================================
            *
            * Setelah titik ini refund resmi dimiliki oleh proses ini.
            *
            * processing_at penting untuk recovery job/scheduler.
            */
            $refund->update([
                'status' => Refund::STATUS_PROCESSING,
                'processing_at' => now(),
            ]);

            /*
            * Fresh relation digunakan setelah lock dan update.
            *
            * Provider dipanggil DI LUAR transaction.
            */
            return $refund->fresh([
                'order',
                'payment',
            ]);
        });

        /*
        * ================================================================
        * PROVIDER CALL OUTSIDE DATABASE TRANSACTION
        * ================================================================
        *
        * Jangan pernah memegang database transaction ketika melakukan
        * HTTP request ke Stripe/Midtrans.
        *
        * Kalau provider lambat, database row lock tidak ikut tertahan.
        */
        return match ($refund->payment->provider) {
            'stripe' => $this->processStripe(
                $refund,
                $refund->payment
            ),

            'midtrans' => $this->processMidtrans(
                $refund,
                $refund->payment
            ),

            'cod' => $this->processCod(
                $refund
            ),

            default => throw new \LogicException(
                'Unsupported refund provider.'
            ),
        };
    }

    /**
     * Memulihkan refund yang terhenti pada status processing.
     *
     * Method ini DIPANGGIL oleh recovery job/scheduler,
     * bukan oleh RefundController.
     */
    public function recoverProcessing(Refund $refund): Refund
    {
        $refund = Refund::query()
            ->with(['order', 'payment'])
            ->findOrFail($refund->id);

        if ($refund->status !== Refund::STATUS_PROCESSING) {
            return $refund;
        }

        if (! $refund->processing_at) {
            return $refund;
        }

        return match ($refund->payment?->provider) {
            'stripe' => $this->recoverStripe(
                $refund,
                $refund->payment
            ),

            'midtrans' => $this->recoverMidtrans(
                $refund,
                $refund->payment
            ),

            'cod' => $this->processCod($refund),

            default => $this->markFailed(
                $refund,
                "Provider pembayaran [{$refund->payment?->provider}] tidak didukung."
            ),
        };
    }

    /**
     * Recovery Stripe.
     *
     * PENTING:
     * menggunakan idempotency key yang SAMA dengan processStripe().
     * Jadi retry tidak membuat refund Stripe kedua.
     */
    protected function recoverStripe(
        Refund $refund,
        ?Payment $payment
    ): Refund {
        if (! $payment) {
            return $this->markFailed(
                $refund,
                'Payment untuk refund tidak ditemukan.'
            );
        }

        if (! $payment->stripe_payment_intent_id) {
            return $this->markFailed(
                $refund,
                'Stripe PaymentIntent ID tidak tersedia.'
            );
        }

        try {
            $stripeRefund = $this->stripe->refund(
                $payment->stripe_payment_intent_id,
                (int) round((float) $refund->amount * 100),
                'requested_by_customer',
                $this->refundKey($refund)
            );

            return $this->storeStripeResult(
                $refund,
                $stripeRefund
            );
        } catch (\Throwable $exception) {
            /*
             * Jangan menganggap semua exception sebagai refund gagal.
             *
             * Pada kondisi network timeout, Stripe mungkin sebenarnya
             * sudah menerima refund. Karena idempotency key tetap sama,
             * recovery berikutnya dapat mengulangi request dengan aman.
             *
             * Oleh karena itu refund tetap PROCESSING.
             */
            $metadata = $refund->metadata ?? [];

            $metadata['last_recovery_error'] = $exception->getMessage();
            $metadata['last_recovery_attempt_at'] = now()->toIso8601String();

            $refund->update([
                'status' => Refund::STATUS_PROCESSING,
                'metadata' => $metadata,
            ]);

            return $refund->fresh();
        }
    }

    /**
     * Recovery Midtrans.
     *
     * Menggunakan refund key yang sama.
     */
    protected function recoverMidtrans(
        Refund $refund,
        ?Payment $payment
    ): Refund {
        if (! $payment) {
            return $this->markFailed(
                $refund,
                'Payment untuk refund tidak ditemukan.'
            );
        }

        $identifier =
            $payment->transaction_id
            ?: $refund->order?->order_number;

        if (! $identifier) {
            return $this->markFailed(
                $refund,
                'Identifier transaksi Midtrans tidak tersedia.'
            );
        }

        try {
            $response = $this->midtrans->refund(
                $identifier,
                (int) round((float) $refund->amount),
                $this->refundKey($refund),
                $refund->reason
            );

            return $this->storeMidtransResult(
                $refund,
                $response
            );
        } catch (\Throwable $exception) {
            /*
             * Sama seperti Stripe:
             * network error bersifat ambiguous.
             * Jangan langsung menyatakan refund gagal.
             */
            $metadata = $refund->metadata ?? [];

            $metadata['last_recovery_error'] = $exception->getMessage();
            $metadata['last_recovery_attempt_at'] = now()->toIso8601String();

            $refund->update([
                'status' => Refund::STATUS_PROCESSING,
                'metadata' => $metadata,
            ]);

            return $refund->fresh();
        }
    }

    /**
     * Memproses refund melalui Stripe untuk request baru.
     */
    protected function processStripe(
        Refund $refund,
        Payment $payment
    ): Refund {
        if (! $payment->stripe_payment_intent_id) {
            return $this->markFailed(
                $refund,
                'Stripe PaymentIntent ID tidak tersedia.'
            );
        }

        try {
            $stripeRefund = $this->stripe->refund(
                $payment->stripe_payment_intent_id,
                (int) round((float) $refund->amount * 100),
                'requested_by_customer',
                $this->refundKey($refund)
            );

            return $this->storeStripeResult(
                $refund,
                $stripeRefund
            );
        } catch (\Throwable $exception) {
            /*
             * Request provider sudah dilakukan.
             * Jika timeout/network error, jangan langsung failed karena
             * provider mungkin sudah menerima refund.
             */
            $metadata = $refund->metadata ?? [];

            $metadata['last_provider_error'] = $exception->getMessage();
            $metadata['last_provider_attempt_at'] =
                now()->toIso8601String();

            $refund->update([
                'status' => Refund::STATUS_PROCESSING,
                'metadata' => $metadata,
            ]);

            return $refund->fresh();
        }
    }

    /**
     * Menyimpan hasil refund Stripe ke database.
     */
    protected function storeStripeResult(
        Refund $refund,
        object $stripeRefund
    ): Refund {
        $status = match ($stripeRefund->status ?? null) {
            'succeeded' => Refund::STATUS_COMPLETED,

            'pending',
            'requires_action' => Refund::STATUS_PROCESSING,

            'failed' => Refund::STATUS_FAILED,

            'canceled' => Refund::STATUS_REJECTED,

            default => Refund::STATUS_PROCESSING,
        };

        $metadata = $refund->metadata ?? [];

        $metadata['stripe_refund'] =
            $stripeRefund->toArray();

        $refund->update([
            'status' => $status,
            'reference_id' => $stripeRefund->id ?? null,
            'processed_at' => $status === Refund::STATUS_COMPLETED
                ? now()
                : null,
            'metadata' => $metadata,
        ]);

        return $refund->fresh();
    }

    /**
     * Memproses refund melalui Midtrans untuk request baru.
     */
    protected function processMidtrans(
        Refund $refund,
        Payment $payment
    ): Refund {
        $identifier =
            $payment->transaction_id
            ?: $refund->order->order_number;

        if (! $identifier) {
            return $this->markFailed(
                $refund,
                'Identifier transaksi Midtrans tidak tersedia.'
            );
        }

        try {
            $response = $this->midtrans->refund(
                $identifier,
                (int) round((float) $refund->amount),
                $this->refundKey($refund),
                $refund->reason
            );

            return $this->storeMidtransResult(
                $refund,
                $response
            );
        } catch (\Throwable $exception) {
            $metadata = $refund->metadata ?? [];

            $metadata['last_provider_error'] = $exception->getMessage();
            $metadata['last_provider_attempt_at'] =
                now()->toIso8601String();

            $refund->update([
                'status' => Refund::STATUS_PROCESSING,
                'metadata' => $metadata,
            ]);

            return $refund->fresh();
        }
    }

    /**
     * Menyimpan hasil refund Midtrans.
     */
    protected function storeMidtransResult(
        Refund $refund,
        object $response
    ): Refund {
        $transactionStatus =
            $response->transaction_status ?? null;

        $status = match ($transactionStatus) {
            'refund',
            'partial_refund' => Refund::STATUS_COMPLETED,

            default => Refund::STATUS_PROCESSING,
        };

        $metadata = $refund->metadata ?? [];

        $metadata['midtrans'] = (array) $response;

        $refund->update([
            'status' => $status,
            'reference_id' => $response->refund_key
                ?? $response->refund_chargeback_id
                ?? null,
            'processed_at' => $status === Refund::STATUS_COMPLETED
                ? now()
                : null,
            'metadata' => $metadata,
        ]);

        return $refund->fresh();
    }

    /**
     * Refund COD diproses secara manual oleh admin.
     */
    protected function processCod(
        Refund $refund
    ): Refund {
        $refund->update([
            'status' => Refund::STATUS_APPROVED,
            'processed_at' => now(),
        ]);

        return $refund->fresh();
    }

    /**
     * Membuat idempotency key yang stabil untuk satu refund.
     *
     * Jangan menggunakan random UUID/timestamp di sini.
     * Recovery harus menggunakan key yang sama.
     */
    protected function refundKey(
        Refund $refund
    ): string {
        return 'refund-'.$refund->id;
    }

    /**
     * Menandai refund sebagai gagal secara deterministik.
     */
    protected function markFailed(
        Refund $refund,
        string $message
    ): Refund {
        $metadata = $refund->metadata ?? [];

        $metadata['error'] = $message;

        $refund->update([
            'status' => Refund::STATUS_FAILED,
            'processed_at' => now(),
            'metadata' => $metadata,
        ]);

        return $refund->fresh();
    }
}

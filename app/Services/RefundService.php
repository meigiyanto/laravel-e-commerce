<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Payment;
use App\Models\Refund;
use App\Models\User;
use App\Services\MidtransService;
use App\Services\RefundService;
use App\Services\StripePaymentService;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Mockery;
use Tests\TestCase;

class RefundService
{
    public function __construct(
        protected StripePaymentService $stripe,
        protected MidtransService $midtrans,
    ) {
    }

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

            if (!$payment) {
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

            $refundableAmount = $this->refundableAmount($payment);

            if ($amount <= 0) {
                throw ValidationException::withMessages([
                    'amount' => 'Nominal refund harus lebih besar dari 0.',
                ]);
            }

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
     * Memproses refund ke payment provider.
     */
    public function process(Refund $refund): Refund
    {
        return DB::transaction(function () use ($refund) {
            $refund = Refund::query()
                ->with(['order', 'payment'])
                ->lockForUpdate()
                ->findOrFail($refund->id);

            if ($refund->status !== Refund::STATUS_REQUESTED) {
                throw ValidationException::withMessages([
                    'refund' => 'Refund ini sudah diproses atau tidak dapat diproses kembali.',
                ]);
            }

            $payment = $refund->payment;

            if ($payment->status !== 'succeeded') {
                throw ValidationException::withMessages([
                    'payment' => 'Payment belum berada pada status succeeded.',
                ]);
            }

            $refund->update([
                'status' => Refund::STATUS_PROCESSING,
            ]);

            return match ($payment->provider) {
                'stripe' => $this->processStripe(
                    $refund,
                    $payment
                ),

                'midtrans' => $this->processMidtrans(
                    $refund,
                    $payment
                ),

                'cod' => $this->processCod(
                    $refund
                ),

                default => throw ValidationException::withMessages([
                    'provider' => "Provider pembayaran [{$payment->provider}] belum didukung untuk refund.",
                ]),
            };
        });
    }

    protected function processStripe(
        Refund $refund,
        Payment $payment
    ): Refund {
        if (!$payment->stripe_payment_intent_id) {
            $this->markFailed(
                $refund,
                'Stripe PaymentIntent ID tidak tersedia.'
            );

            return $refund->fresh();
        }

        try {
            $stripeRefund = $this->stripe->refund(
                $payment->stripe_payment_intent_id,
                (int) round(
                    (float) $refund->amount * 100
                )
            );

            $status = match ($stripeRefund->status) {
                'succeeded' => Refund::STATUS_COMPLETED,
                'pending',
                'requires_action' => Refund::STATUS_PROCESSING,
                'failed' => Refund::STATUS_FAILED,
                'canceled' => Refund::STATUS_REJECTED,
                default => Refund::STATUS_PROCESSING,
            };

            $refund->update([
                'status' => $status,
                'reference_id' => $stripeRefund->id,
                'processed_at' => $status === Refund::STATUS_COMPLETED
                    ? now()
                    : null,
                'metadata' => [
                    'stripe_refund' => $stripeRefund->toArray(),
                ],
            ]);

            return $refund->fresh();
        } catch (\Throwable $exception) {
            $this->markFailed(
                $refund,
                $exception->getMessage()
            );

            return $refund->fresh();
        }
    }

    protected function processMidtrans(
        Refund $refund,
        Payment $payment
    ): Refund {
        $identifier =
            $payment->transaction_id
            ?: $refund->order->order_number;

        if (!$identifier) {
            $this->markFailed(
                $refund,
                'Identifier transaksi Midtrans tidak tersedia.'
            );

            return $refund->fresh();
        }

        try {
            $response = $this->midtrans->refund(
                $identifier,
                (int) round(
                    (float) $refund->amount
                ),
                $this->refundKey($refund),
                $refund->reason
            );

            $transactionStatus =
                $response->transaction_status ?? null;

            $status = match ($transactionStatus) {
                'refund' => Refund::STATUS_COMPLETED,

                'partial_refund' => Refund::STATUS_COMPLETED,

                default => Refund::STATUS_PROCESSING,
            };

            $refund->update([
                'status' => $status,
                'reference_id' =>
                    $response->refund_key
                    ?? $response->refund_chargeback_id
                    ?? null,
                'processed_at' => $status === Refund::STATUS_COMPLETED
                    ? now()
                    : null,
                'metadata' => [
                    'midtrans' => (array) $response,
                ],
            ]);

            return $refund->fresh();
        } catch (\Throwable $exception) {
            $this->markFailed(
                $refund,
                $exception->getMessage()
            );

            return $refund->fresh();
        }
    }

    protected function processCod(
        Refund $refund
    ): Refund {
        /*
         * COD tidak mempunyai payment gateway
         * yang dapat kita panggil untuk mengembalikan dana.
         *
         * Untuk sementara refund COD harus diproses
         * secara manual oleh admin.
         */

        $refund->update([
            'status' => Refund::STATUS_APPROVED,
        ]);

        return $refund->fresh();
    }

    protected function refundKey(Refund $refund): string
    {
        return 'refund-' . $refund->id;
    }

    protected function markFailed(
        Refund $refund,
        string $message
    ): void {
        $metadata = $refund->metadata ?? [];

        $metadata['error'] = $message;

        $refund->update([
            'status' => Refund::STATUS_FAILED,
            'processed_at' => now(),
            'metadata' => $metadata,
        ]);
    }

    public function test_stripe_refund_is_completed_when_provider_succeeds(): void
    {
        $user = User::factory()->create();

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'completed',
            'total' => 150000,
        ]);

        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'provider' => 'stripe',
            'status' => 'succeeded',
            'stripe_payment_intent_id' => 'pi_test_123',
            'gross_amount' => 150000,
        ]);

        $refund = Refund::create([
            'order_id' => $order->id,
            'payment_id' => $payment->id,
            'amount' => 50000,
            'currency' => 'IDR',
            'reason' => 'Produk rusak',
            'status' => Refund::STATUS_REQUESTED,
            'provider' => 'stripe',
            'requested_at' => now(),
        ]);

        $stripeRefund = Mockery::mock();

        $stripeRefund->status = 'succeeded';
        $stripeRefund->id = 're_test_123';

        $stripe = Mockery::mock(StripePaymentService::class);

        $stripe
            ->shouldReceive('refund')
            ->once()
            ->with('pi_test_123', 5000000)
            ->andReturn($stripeRefund);

        $midtrans = Mockery::mock(MidtransService::class);

        $service = new RefundService(
            $stripe,
            $midtrans
        );

        $result = $service->process($refund);

        $this->assertSame(
            Refund::STATUS_COMPLETED,
            $result->status
        );

        $this->assertSame(
            're_test_123',
            $result->reference_id
        );

        $this->assertNotNull($result->processed_at);

        $this->assertDatabaseHas('refunds', [
            'id' => $refund->id,
            'status' => Refund::STATUS_COMPLETED,
            'reference_id' => 're_test_123',
        ]);
    }
}
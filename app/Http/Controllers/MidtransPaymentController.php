<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Payment;
use App\Services\MidtransService;
use App\Services\RefundService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class MidtransPaymentController extends Controller
{
    public function __construct(
        private MidtransService $midtrans,
        private RefundService $refundService
    ) {}

    /**
     * Menampilkan halaman pembayaran Midtrans.
     */
    public function show(Order $order)
    {
        abort_unless($order->user_id === auth()->id(), 403);
        $order->load(['payment', 'items.product', 'user']);
        $payment = $order->payment;
        if (! $payment) {
            abort(404, 'Data payment not found.');
        }

        /*
         * Halaman ini hanya untuk pembayaran Midtrans.
         */
        if ($payment->provider !== 'midtrans') {
            return redirect()->route('payment.show', $order);
        }

        /*
         * Pembayaran sudah berhasil.
         * Tidak perlu membuat Snap Token baru.
         */
        if ($payment->status === 'succeeded') {
            return redirect()->route('checkout.success', $order);
        }

        try {
            /*
             * Gunakan Snap Token yang sudah tersimpan.
             */
            $snapToken = $payment->midtrans_snap_token;

            /*
             * Buat Snap Token hanya jika belum tersedia.
             */
            if (! $snapToken) {
                $snapToken = $this->midtrans->createSnapToken($order);

                $payment->update([
                    'provider' => 'midtrans',
                    'currency' => 'IDR',
                    'status' => 'pending',
                    'payment_type' => 'midtrans_snap',
                    'transaction_status' => 'pending',
                    'gross_amount' => $order->total,
                    'midtrans_snap_token' => $snapToken,
                ]);
            }

            return view('storefront.midtrans-payment',
                [
                    'order' => $order,
                    'payment' => $payment->fresh(),
                    'snapToken' => $snapToken,
                    'clientKey' => config('midtrans.client_key'),
                ]
            );
        } catch (Throwable $e) {
            Log::error('Midtrans Snap Token creation failed.',
                [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'message' => $e->getMessage(),
                ]
            );

            abort(502, 'Midtrans payment cannot be prepared..');
        }
    }

    /**
     * Verifikasi pembayaran setelah callback Snap.
     *
     * Callback browser bukan sumber kebenaran pembayaran.
     * Status transaksi diverifikasi kembali melalui
     * Midtrans Get Status API.
     */
    public function confirm(
        Request $request,
        Order $order
    ): JsonResponse {
        abort_unless($order->user_id === auth()->id(), 403);

        $validated = $request->validate([
            'transaction_id' => [
                'nullable',
                'string',
                'max:255',
            ],
        ]);

        $order->load('payment');

        $payment = $order->payment;

        if (! $payment) {
            return response()->json([
                'message' => 'Data pembayaran tidak ditemukan.',
            ], 404);
        }

        if ($payment->provider !== 'midtrans') {
            return response()->json(['message' => 'Provider pembayaran tidak sesuai.'], 409);
        }

        /*
         * Idempotent:
         * jangan melakukan request Midtrans lagi jika
         * pembayaran sudah berhasil diverifikasi.
         */
        if ($payment->status === 'succeeded') {
            return response()->json([
                'success' => true,
                'status' => 'succeeded',
                'redirect_url' => route('checkout.success', $order),
            ]);
        }

        try {
            /*
             * Callback Snap biasanya memberikan transaction_id.
             *
             * Jika tidak tersedia, gunakan transaction_id
             * yang sudah tersimpan atau order_number.
             */
            $transactionIdentifier = $validated['transaction_id'] ?? $payment->transaction_id ?? $order->order_number;

            // Identitas transaksi berasal dari pesanan di database,
            // bukan dari transaction_id yang dikirim browser.
            $transaction = $this->midtrans->getStatus(
                $order->order_number
            );

            // Transaksi harus merujuk ke pesanan yang sedang dikonfirmasi.
            if ((string) ($transaction->order_id ?? '') !== (string) $order->order_number) {
                Log::warning('Midtrans order ID mismatch.', [
                    'order_id' => $order->id,
                    'expected_order_number' => $order->order_number,
                    'received_order_id' => $transaction->order_id ?? null,
                ]);

                return response()->json([
                    'message' => 'Your transaction did not match with order.',
                ], 409);
            }

            // Nominal dari gateway harus sama dengan nominal pesanan.
            if (
                ! isset($transaction->gross_amount)
                || ! $this->amountsMatch(
                    $transaction->gross_amount,
                    $order->total
                )
            ) {
                Log::critical('Midtrans amount mismatch.', [
                    'order_id' => $order->id,
                    'expected_amount' => $order->total,
                    'received_amount' => $transaction->gross_amount ?? null,
                ]);

                return response()->json([
                    'message' => 'Transaction value did not match.',
                ], 422);
            }


            $result = DB::transaction(function () use (
                $order,
                $transaction
            ) {
                $lockedOrder = Order::query()
                    ->lockForUpdate()
                    ->findOrFail($order->id);

                $lockedPayment = Payment::query()
                    ->where('order_id', $lockedOrder->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($lockedPayment->provider !== 'midtrans') {
                    throw new \RuntimeException(
                        'Provider pembayaran tidak sesuai.'
                    );
                }

                // Pembayaran mungkin sudah berhasil diproses
                // oleh request lain saat pemeriksaan gateway berjalan.
                if ($lockedPayment->status === 'succeeded') {
                    return 'already_succeeded';
                }

                $incomingStatus = $this->resolvePaymentStatus(
                    strtolower(
                        (string) ($transaction->transaction_status ?? 'pending')
                    ),
                    isset($transaction->status_code)
                        ? (string) $transaction->status_code
                        : null,
                    isset($transaction->fraud_status)
                        ? strtolower((string) $transaction->fraud_status)
                        : null
                );

                // Jangan menurunkan status terminal menjadi pending.
                if (
                    in_array(
                        $lockedPayment->status,
                        ['failed', 'expired', 'canceled'],
                        true
                    )
                    && $incomingStatus === 'pending'
                ) {
                    return 'ignored_stale';
                }

                $this->syncPaymentFromMidtrans(
                    $lockedOrder,
                    $lockedPayment,
                    $transaction
                );

                return 'processed';
            });

            $payment->refresh();
            $order->refresh();

            /*
             * Pembayaran sudah berhasil diverifikasi.
             */
            if ($payment->status === 'succeeded') {
                return response()->json([
                    'success' => true,
                    'status' => 'succeeded',
                    'redirect_url' => route('checkout.success', $order),
                ]);
            }

            /*
             * Pembayaran belum berhasil.
             */
            return response()->json([
                'success' => false,
                'status' => $payment->status,
                'transaction_status' => $payment->transaction_status,
                'message' => $this->paymentStatusMessage($payment),
            ], 422);
        } catch (Throwable $e) {
            Log::error('Midtrans payment confirmation failed.',
                [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'message' => $e->getMessage(),
                ]
            );

            return response()->json(['message' => 'Payment could not be verified.'], 502);
        }
    }

    /**
     * HTTP Notification / Webhook Midtrans.
     *
     * Callback diverifikasi dengan signature, kemudian status
     * transaksi diverifikasi kembali melalui Midtrans Get Status API.
     */
    public function notification(Request $request): JsonResponse
    {
        $payload = $request->all();

        // Hindari menyimpan seluruh payload pelanggan/transaksi ke log.
        $requiredFields = [
            'order_id',
            'status_code',
            'gross_amount',
            'signature_key',
        ];

        foreach ($requiredFields as $field) {
            if (
                ! array_key_exists($field, $payload)
                || ! is_scalar($payload[$field])
                || (string) $payload[$field] === ''
            ) {
                return response()->json([
                    'message' => "Field {$field} tidak valid.",
                ], 422);
            }
        }

        if (! is_numeric($payload['gross_amount'])) {
            return response()->json([
                'message' => 'Nominal transaksi tidak valid.',
            ], 422);
        }

        // 1. Verifikasi signature sebelum mempercayai payload.
        if (! $this->midtrans->verifyNotification($payload)) {
            Log::warning('Invalid Midtrans notification signature.', [
                'order_id' => (string) $payload['order_id'],
            ]);

            return response()->json([
                'message' => 'Signature notifikasi tidak valid.',
            ], 403);
        }

        try {
            // 2. Cari pesanan dari order_id yang dikirim Midtrans.
            $order = Order::where('order_number', (string) $payload['order_id'])->first();

            if (! $order) {
                return response()->json([
                    'message' => 'Pesanan tidak ditemukan.',
                ], 404);
            }

            // 3. Pastikan pesanan memiliki pembayaran Midtrans.
            $payment = $order->payment;

            if (! $payment) {
                return response()->json([
                    'message' => 'Data pembayaran tidak ditemukan.',
                ], 404);
            }

            if ($payment->provider !== 'midtrans') {
                Log::warning('Midtrans provider mismatch.', [
                    'order_id' => $order->id,
                    'provider' => $payment->provider,
                ]);

                return response()->json([
                    'message' => 'Provider pembayaran tidak sesuai.',
                ], 409);
            }

            // 4. Cocokkan nominal pada notifikasi dengan database.
            if (! $this->amountsMatch(
                $payload['gross_amount'],
                $order->total
            )) {
                Log::critical('Midtrans notification amount mismatch.', [
                    'order_id' => $order->id,
                ]);

                return response()->json([
                    'message' => 'Nominal notifikasi tidak sesuai.',
                ], 422);
            }

            // Notifikasi duplikat: jika sudah berhasil, jangan proses ulang.
            if ($payment->status === 'succeeded') {
                return response()->json([
                    'success' => true,
                    'message' => 'Pembayaran sudah diproses sebelumnya.',
                ]);
            }

            // 5. Ambil status terbaru dari API Midtrans.
            // Jangan menggunakan payload webhook sebagai satu-satunya
            // sumber status pembayaran.
            $transaction = $this->midtrans->getStatus(
                $order->order_number
            );

            // 6. Pastikan respons API benar-benar milik pesanan ini.
            if (
                (string) ($transaction->order_id ?? '')
                !== (string) $order->order_number
            ) {
                Log::critical('Midtrans API order ID mismatch.', [
                    'order_id' => $order->id,
                ]);

                return response()->json([
                    'message' => 'Identitas transaksi tidak cocok.',
                ], 409);
            }

            if (
                ! isset($transaction->gross_amount)
                || ! is_numeric($transaction->gross_amount)
                || ! $this->amountsMatch(
                    $transaction->gross_amount,
                    $order->total
                )
            ) {
                Log::critical('Midtrans API amount mismatch.', [
                    'order_id' => $order->id,
                ]);

                return response()->json([
                    'message' => 'Nominal transaksi gateway tidak cocok.',
                ], 422);
            }

            // 7. Kunci pesanan dan pembayaran untuk mencegah
            // dua notifikasi bersamaan mengubah status secara bersaing.
            $result = DB::transaction(function () use ($order, $transaction) {
                $lockedOrder = Order::whereKey($order->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $lockedPayment = Payment::where(
                    'order_id',
                    $lockedOrder->id
                )->lockForUpdate()->first();

                if (! $lockedPayment) {
                    throw new \RuntimeException(
                        'Data pembayaran tidak ditemukan.'
                    );
                }

                if ($lockedPayment->provider !== 'midtrans') {
                    throw new \RuntimeException(
                        'Provider pembayaran berubah saat pemrosesan.'
                    );
                }

                // Request paralel mungkin sudah menyelesaikan pembayaran.
                if ($lockedPayment->status === 'succeeded') {
                    return 'already_succeeded';
                }

                // Jangan menurunkan status terminal menjadi pending
                // akibat notifikasi lama atau status gateway yang belum final.
                $incomingStatus = $this->resolvePaymentStatus(
                    strtolower((string) ($transaction->transaction_status ?? 'pending')),
                    isset($transaction->status_code)
                        ? (string) $transaction->status_code
                        : null,
                    isset($transaction->fraud_status)
                        ? strtolower((string) $transaction->fraud_status)
                        : null
                );

                if (
                    in_array(
                        $lockedPayment->status,
                        ['failed', 'expired', 'canceled'],
                        true
                    )
                    && $incomingStatus === 'pending'
                ) {
                    return 'ignored_stale';
                }

                // 8. Satu-satunya tempat sinkronisasi dilakukan.
                $this->syncPaymentFromMidtrans(
                    $lockedOrder,
                    $lockedPayment,
                    $transaction
                );

                return 'processed';
            });

            if ($result === 'ignored_stale') {
                Log::warning('Ignored stale Midtrans notification.', [
                    'order_id' => $order->id,
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => match ($result) {
                    'already_succeeded' => 'Pembayaran sudah diproses sebelumnya.',
                    'ignored_stale' => 'Notifikasi lama diabaikan.',
                    default => 'Notifikasi berhasil diproses.',
                },
            ]);
        } catch (Throwable $e) {
            Log::error('Midtrans notification processing failed.', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'order_id' => (string) $payload['order_id'],
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'Notifikasi belum dapat diproses.',
            ], 500);
        }
    }


    /**
     * Sinkronisasi status transaksi Midtrans
     * ke database lokal.
     */
    private function syncPaymentFromMidtrans(
        Order $order,
        Payment $payment,
        object $transaction
    ): void {
        $transactionStatus = strtolower((string) ($transaction->transaction_status ?? 'pending'));

        $statusCode = isset($transaction->status_code) ? (string) $transaction->status_code : null;

        $paymentType = $transaction->payment_type ?? null;
        $transactionId = $transaction->transaction_id ?? null;
        $fraudStatus = isset($transaction->fraud_status) ? strtolower((string) $transaction->fraud_status) : null;

        /*
         * Tentukan status lokal berdasarkan
         * status transaksi Midtrans.
         */
        $paymentStatus = $this->resolvePaymentStatus($transactionStatus, $statusCode, $fraudStatus);

        $data = [
            'provider' => 'midtrans',
            'status' => $paymentStatus,
            'payment_method' => $paymentType,
            'transaction_id' => $transactionId,
            'reference_id' => $transaction->reference_id ?? $payment->reference_id,
            'payment_type' => $paymentType,
            'transaction_status' => $transactionStatus,
            'fraud_status' => $fraudStatus,
            'status_code' => $statusCode,
            'gross_amount' => $transaction->gross_amount ?? $order->total,
            'metadata' => [
                'midtrans' => (array) $transaction,
            ],
        ];

        /*
         * Simpan waktu pembayaran hanya sekali.
         */
        if ($paymentStatus === 'succeeded') {
            $data['paid_at'] = now();

            if ($order->status === 'pending') {
                $order->update([
                    'status' => 'processing',
                ]);
            }
        }

        /*
         * Simpan expiry time jika dikirim Midtrans.
         */
        if (! empty($transaction->expiry_time)) {
            $data['expires_at'] = $transaction->expiry_time;
        }

        $payment->update($data);

        if (
            $paymentStatus === 'succeeded'
            && $order->status === 'canceled'
        ) {
            $refund = $this->refundService
                ->requestLateCanceledOrderRefund(
                    $order,
                    $payment->fresh()
                );

            Log::warning(
                'Midtrans payment succeeded for a canceled order.',
                [
                    'order_id' => $order->id,
                    'payment_id' => $payment->id,
                    'refund_id' => $refund?->id,
                    'refund_status' => $refund?->status,
                ]
            );
        }
    }

    /**
     * Menentukan status pembayaran lokal
     * berdasarkan status transaksi Midtrans.
     */
    private function resolvePaymentStatus(
        string $transactionStatus,
        ?string $statusCode,
        ?string $fraudStatus
    ): string {
        /*
         * Settlement adalah pembayaran berhasil.
         */
        if ($transactionStatus === 'settlement') {
            return 'succeeded';
        }

        /*
         * Capture untuk kartu harus mempertimbangkan
         * status code dan fraud status.
         */
        if ($transactionStatus === 'capture') {
            $validStatusCode = $statusCode === null || $statusCode === '200';
            $validFraudStatus = $fraudStatus === null || $fraudStatus === 'accept';

            return ($validStatusCode && $validFraudStatus) ? 'succeeded' : 'failed';
        }

        return match ($transactionStatus) {
            'deny',
            'cancel' => 'failed',
            'canceled' => 'canceled',
            'expire' => 'expired',
            'failure' => 'failed',
            default => 'pending',
        };
    }

    /**
     * Membandingkan nominal tanpa membutuhkan BCMath.
     */
    private function amountsMatch(
        string|int|float $received,
        string|int|float $expected
    ): bool {
        return number_format((float) $received, 2, '.', '') === number_format((float) $expected, 2, '.', '');
    }

    /**
     * Pesan yang ditampilkan ketika pembayaran
     * belum berhasil.
     */
    private function paymentStatusMessage(
        Payment $payment
    ): string {
        return match ($payment->status) {
            'failed' => 'Midtrans payment failed.',
            'canceled' => 'Midtrans payment canceled.',
            'expired' => 'Midtrans payment has been expired.',
            default => 'Payment has not been completed successfully.',
        };
    }

    private function webhookEventId(array $payload): string
    {
        $normalized = $payload;
        ksort($normalized);

        return hash('sha256', json_encode($normalized, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION));
    }
}

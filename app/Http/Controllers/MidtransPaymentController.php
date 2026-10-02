<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Payment;
use App\Services\MidtransService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class MidtransPaymentController extends Controller
{
    public function __construct(
        private MidtransService $midtrans
    ) {
    }

    /**
     * Menampilkan halaman pembayaran Midtrans.
     */
    public function show(Order $order)
    {
        abort_unless(
            $order->user_id === auth()->id(),
            403
        );

        $order->load([
            'payment',
            'items.product',
            'user',
        ]);

        $payment = $order->payment;

        if (!$payment) {
            abort(404, 'Data pembayaran tidak ditemukan.');
        }

        /*
         * Halaman ini hanya untuk pembayaran Midtrans.
         */
        if ($payment->provider !== 'midtrans') {
            return redirect()
                ->route('payment.show', $order);
        }

        /*
         * Pembayaran sudah berhasil.
         * Tidak perlu membuat Snap Token baru.
         */
        if ($payment->status === 'succeeded') {
            return redirect()
                ->route('checkout.success', $order);
        }

        try {
            /*
             * Gunakan Snap Token yang sudah tersimpan.
             */
            $snapToken = $payment->midtrans_snap_token;

            /*
             * Buat Snap Token hanya jika belum tersedia.
             */
            if (!$snapToken) {
                $snapToken = $this->midtrans->createSnapToken(
                    $order
                );

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

            return view(
                'storefront.midtrans-payment',
                [
                    'order' => $order,
                    'payment' => $payment->fresh(),
                    'snapToken' => $snapToken,
                    'clientKey' => config(
                        'midtrans.client_key'
                    ),
                ]
            );
        } catch (Throwable $e) {
            Log::error(
                'Midtrans Snap Token creation failed.',
                [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'message' => $e->getMessage(),
                ]
            );

            abort(
                502,
                'Pembayaran Midtrans tidak dapat disiapkan.'
            );
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
        abort_unless(
            $order->user_id === auth()->id(),
            403
        );

        $validated = $request->validate([
            'transaction_id' => [
                'nullable',
                'string',
                'max:255',
            ],
        ]);

        $order->load('payment');

        $payment = $order->payment;

        if (!$payment) {
            return response()->json([
                'message' =>
                    'Data pembayaran tidak ditemukan.',
            ], 404);
        }

        if ($payment->provider !== 'midtrans') {
            return response()->json([
                'message' =>
                    'Provider pembayaran tidak sesuai.',
            ], 409);
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
                'redirect_url' => route(
                    'checkout.success',
                    $order
                ),
            ]);
        }

        try {
            /*
             * Callback Snap biasanya memberikan transaction_id.
             *
             * Jika tidak tersedia, gunakan transaction_id
             * yang sudah tersimpan atau order_number.
             */
            $transactionIdentifier =
                $validated['transaction_id']
                ?? $payment->transaction_id
                ?? $order->order_number;

            /*
             * Ambil status terbaru langsung dari Midtrans.
             */
            $transaction = $this->midtrans->getStatus(
                $transactionIdentifier
            );

            $this->syncPaymentFromMidtrans(
                $order,
                $payment,
                $transaction
            );

            $payment->refresh();
            $order->refresh();

            /*
             * Pembayaran sudah berhasil diverifikasi.
             */
            if ($payment->status === 'succeeded') {
                return response()->json([
                    'success' => true,
                    'status' => 'succeeded',
                    'redirect_url' => route(
                        'checkout.success',
                        $order
                    ),
                ]);
            }

            /*
             * Pembayaran belum berhasil.
             */
            return response()->json([
                'success' => false,
                'status' => $payment->status,
                'transaction_status' =>
                    $payment->transaction_status,
                'message' =>
                    $this->paymentStatusMessage($payment),
            ], 422);
        } catch (Throwable $e) {
            Log::error(
                'Midtrans payment confirmation failed.',
                [
                    'order_id' => $order->id,
                    'order_number' =>
                        $order->order_number,
                    'message' => $e->getMessage(),
                ]
            );

            return response()->json([
                'message' =>
                    'Pembayaran tidak dapat diverifikasi.',
            ], 502);
        }
    }

    /**
     * HTTP Notification / Webhook Midtrans.
     *
     * Endpoint ini tidak menggunakan auth middleware
     * karena dipanggil langsung oleh server Midtrans.
     */
    public function notification(
        Request $request
    ): JsonResponse {
        $payload = $request->all();

        Log::info(
            'Midtrans notification received.',
            $payload
        );

        /*
         * Field minimum yang diperlukan untuk
         * memverifikasi notification.
         */
        foreach ([
            'order_id',
            'status_code',
            'gross_amount',
            'signature_key',
        ] as $field) {
            if (!array_key_exists($field, $payload)) {
                return response()->json([
                    'message' =>
                        "Field {$field} tidak ditemukan.",
                ], 422);
            }
        }

        /*
         * Verifikasi signature Midtrans.
         */
        if (
            !$this->midtrans->verifyNotification(
                $payload
            )
        ) {
            Log::warning(
                'Invalid Midtrans notification signature.',
                [
                    'order_id' =>
                        $payload['order_id'] ?? null,
                ]
            );

            return response()->json([
                'message' =>
                    'Signature notification tidak valid.',
            ], 403);
        }

        /*
         * Cari order berdasarkan order_number
         * yang dikirim sebagai order_id oleh Midtrans.
         */
        $order = Order::where(
            'order_number',
            $payload['order_id']
        )->first();

        if (!$order) {
            return response()->json([
                'message' =>
                    'Order tidak ditemukan.',
            ], 404);
        }

        $payment = $order->payment;

        if (!$payment) {
            return response()->json([
                'message' =>
                    'Payment tidak ditemukan.',
            ], 404);
        }

        /*
         * Pastikan notification memang milik
         * pembayaran Midtrans.
         */
        if ($payment->provider !== 'midtrans') {
            return response()->json([
                'message' =>
                    'Payment provider tidak sesuai.',
            ], 409);
        }

        /*
         * Gross amount dari Midtrans harus sama
         * dengan total order di database.
         */
        if (
            !$this->amountsMatch(
                $payload['gross_amount'],
                $order->total
            )
        ) {
            Log::critical(
                'Midtrans gross amount mismatch.',
                [
                    'order_id' => $order->id,
                    'expected' => $order->total,
                    'received' =>
                        $payload['gross_amount'],
                ]
            );

            return response()->json([
                'message' =>
                    'Gross amount tidak sesuai.',
            ], 422);
        }

        try {
            DB::transaction(
                function () use (
                    $order,
                    $payment,
                    $payload
                ) {
                    /*
                     * Lock payment untuk mencegah
                     * race condition ketika notification
                     * datang bersamaan.
                     */
                    $payment = Payment::where(
                        'id',
                        $payment->id
                    )
                        ->lockForUpdate()
                        ->firstOrFail();

                    /*
                     * Jangan pernah menurunkan payment
                     * yang sudah berhasil.
                     */
                    if (
                        $payment->status === 'succeeded'
                    ) {
                        return;
                    }

                    $this->syncPaymentFromMidtrans(
                        $order,
                        $payment,
                        (object) $payload
                    );
                }
            );

            return response()->json([
                'success' => true,
                'message' =>
                    'Notification diterima.',
            ]);
        } catch (Throwable $e) {
            Log::error(
                'Midtrans notification processing failed.',
                [
                    'order_id' => $order->id,
                    'order_number' =>
                        $order->order_number,
                    'message' => $e->getMessage(),
                ]
            );

            return response()->json([
                'message' =>
                    'Notification gagal diproses.',
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
        $transactionStatus =
            strtolower(
                (string) (
                    $transaction->transaction_status
                    ?? 'pending'
                )
            );

        $statusCode =
            isset($transaction->status_code)
                ? (string) $transaction->status_code
                : null;

        $paymentType =
            $transaction->payment_type
            ?? null;

        $transactionId =
            $transaction->transaction_id
            ?? null;

        $fraudStatus =
            isset($transaction->fraud_status)
                ? strtolower(
                    (string) $transaction->fraud_status
                )
                : null;

        /*
         * Tentukan status lokal berdasarkan
         * status transaksi Midtrans.
         */
        $paymentStatus =
            $this->resolvePaymentStatus(
                $transactionStatus,
                $statusCode,
                $fraudStatus
            );

        $data = [
            'provider' => 'midtrans',

            'status' =>
                $paymentStatus,

            'payment_method' =>
                $paymentType,

            'transaction_id' =>
                $transactionId,

            'reference_id' =>
                $transaction->reference_id
                ?? $payment->reference_id,

            'payment_type' =>
                $paymentType,

            'transaction_status' =>
                $transactionStatus,

            'fraud_status' =>
                $fraudStatus,

            'status_code' =>
                $statusCode,

            'gross_amount' =>
                $transaction->gross_amount
                ?? $order->total,

            'metadata' => [
                'midtrans' =>
                    (array) $transaction,
            ],
        ];

        /*
         * Simpan waktu pembayaran hanya sekali.
         */
        if (
            $paymentStatus === 'succeeded' &&
            !$payment->paid_at
        ) {
            $data['paid_at'] = now();

            /*
             * Pembayaran berhasil.
             * Order masuk ke proses berikutnya.
             */
            $order->update([
                'status' => 'processing',
            ]);
        }

        /*
         * Simpan expiry time jika dikirim Midtrans.
         */
        if (
            !empty($transaction->expiry_time)
        ) {
            $data['expires_at'] =
                $transaction->expiry_time;
        }

        $payment->update($data);
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
            $validStatusCode =
                $statusCode === null ||
                $statusCode === '200';

            $validFraudStatus =
                $fraudStatus === null ||
                $fraudStatus === 'accept';

            return (
                $validStatusCode &&
                $validFraudStatus
            )
                ? 'succeeded'
                : 'failed';
        }

        return match ($transactionStatus) {
            'deny' => 'failed',

            'cancel',
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
        return number_format(
            (float) $received,
            2,
            '.',
            ''
        ) === number_format(
            (float) $expected,
            2,
            '.',
            ''
        );
    }

    /**
     * Pesan yang ditampilkan ketika pembayaran
     * belum berhasil.
     */
    private function paymentStatusMessage(
        Payment $payment
    ): string {
        return match ($payment->status) {
            'failed' =>
                'Pembayaran Midtrans gagal.',

            'canceled' =>
                'Pembayaran Midtrans dibatalkan.',

            'expired' =>
                'Pembayaran Midtrans telah kedaluwarsa.',

            default =>
                'Pembayaran belum berhasil diselesaikan.',
        };
    }
}

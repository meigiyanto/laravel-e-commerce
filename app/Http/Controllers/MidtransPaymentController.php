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

        if (!$order->payment) {
            abort(404, 'Data pembayaran tidak ditemukan.');
        }

        $payment = $order->payment;

        /*
         * Pastikan halaman ini hanya digunakan
         * untuk pembayaran Midtrans.
         */
        if ($payment->provider !== 'midtrans') {
            return redirect()
                ->route('payment.show', $order);
        }

        /*
         * Jika pembayaran sudah berhasil,
         * langsung ke halaman success.
         */
        if ($payment->status === 'succeeded') {
            return redirect()->route(
                'checkout.success',
                $order
            );
        }

        try {
            /*
             * Gunakan Snap Token yang sudah ada
             * apabila tersedia.
             */
            $snapToken = $payment->midtrans_snap_token;

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
     * Browser tidak dipercaya sebagai sumber
     * pembayaran berhasil. Status diambil kembali
     * dari Midtrans.
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

        if (!$order->payment) {
            return response()->json([
                'message' =>
                    'Data pembayaran tidak ditemukan.',
            ], 404);
        }

        $payment = $order->payment;

        if ($payment->provider !== 'midtrans') {
            return response()->json([
                'message' =>
                    'Provider pembayaran tidak sesuai.',
            ], 409);
        }

        /*
         * Idempotent.
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
             * Gunakan transaction_id jika tersedia.
             * Jika tidak, gunakan order_number.
             */
            $transactionIdentifier =
                $validated['transaction_id']
                ?? $payment->transaction_id
                ?? $order->order_number;

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

            return response()->json([
                'success' => false,
                'status' => $payment->status,
                'transaction_status' =>
                    $payment->transaction_status,
                'message' =>
                    'Pembayaran belum berhasil diselesaikan.',
            ], 422);

        } catch (Throwable $e) {
            Log::error(
                'Midtrans payment confirmation failed.',
                [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
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
     * Route ini TIDAK menggunakan auth middleware.
     */
    public function notification(
        Request $request
    ): JsonResponse {
        $payload = $request->all();

        Log::info(
            'Midtrans notification received.',
            $payload
        );

        $required = [
            'order_id',
            'status_code',
            'gross_amount',
            'signature_key',
        ];

        foreach ($required as $field) {
            if (
                !array_key_exists($field, $payload)
            ) {
                return response()->json([
                    'message' =>
                        "Field {$field} tidak ditemukan.",
                ], 422);
            }
        }

        /*
         * Verifikasi signature dari Midtrans.
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
         * Notification Midtrans harus berasal
         * dari payment provider Midtrans.
         */
        if ($payment->provider !== 'midtrans') {
            return response()->json([
                'message' =>
                    'Payment provider tidak sesuai.',
            ], 409);
        }

        /*
         * Pastikan nominal notification sama
         * dengan nominal order.
         */
        if (
            bccomp(
                (string) $payload['gross_amount'],
                (string) $order->total,
                2
            ) !== 0
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
            DB::transaction(function () use (
                $order,
                $payment,
                $payload
            ) {
                /*
                 * Lock payment agar notification
                 * yang datang bersamaan tidak
                 * menyebabkan race condition.
                 */
                $payment = Payment::where(
                    'id',
                    $payment->id
                )
                    ->lockForUpdate()
                    ->firstOrFail();

                /*
                 * Jangan downgrade pembayaran
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
            });

            return response()->json([
                'success' => true,
                'message' => 'Notification diterima.',
            ]);
        } catch (Throwable $e) {
            Log::error(
                'Midtrans notification processing failed.',
                [
                    'order_id' => $order->id,
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
            $transaction->transaction_status
            ?? 'pending';

        $statusCode =
            $transaction->status_code
            ?? null;

        $paymentType =
            $transaction->payment_type
            ?? null;

        $transactionId =
            $transaction->transaction_id
            ?? null;

        $fraudStatus =
            $transaction->fraud_status
            ?? null;

        $paymentStatus = match (
            $transactionStatus
        ) {
            'settlement' => 'succeeded',

            'capture' =>
                $fraudStatus === null ||
                $fraudStatus === 'accept'
                    ? 'succeeded'
                    : 'failed',

            'deny' => 'failed',

            'cancel',
            'canceled' => 'canceled',

            'expire' => 'expired',

            'failure' => 'failed',

            default => 'pending',
        };

        $data = [
            'provider' => 'midtrans',
            'status' => $paymentStatus,
            'payment_method' =>
                $paymentType,
            'transaction_id' =>
                $transactionId,
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

        if (
            $paymentStatus === 'succeeded'
        ) {
            $data['paid_at'] =
                now();

            $order->update([
                'status' => 'processing',
            ]);
        }

        $payment->update($data);
    }
}

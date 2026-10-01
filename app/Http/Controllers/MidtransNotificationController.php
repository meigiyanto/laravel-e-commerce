<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MidtransNotificationController extends Controller
{
    public function handle(Request $request)
    {
        $payload = $request->all();

        $orderId = $payload['order_id'] ?? null;
        $statusCode = $payload['status_code'] ?? null;
        $grossAmount = $payload['gross_amount'] ?? null;
        $signatureKey = $payload['signature_key'] ?? null;
        $transactionStatus = $payload['transaction_status'] ?? null;
        $fraudStatus = $payload['fraud_status'] ?? null;

        /*
         * Validasi field wajib.
         */
        if (
            !$orderId ||
            !$statusCode ||
            !$grossAmount ||
            !$signatureKey ||
            !$transactionStatus
        ) {
            return response()->json([
                'message' => 'Invalid notification.',
            ], 400);
        }

        /*
         * Verifikasi signature Midtrans.
         *
         * SHA512(
         *     order_id +
         *     status_code +
         *     gross_amount +
         *     ServerKey
         * )
         */
        $expectedSignature = hash(
            'sha512',
            $orderId .
            $statusCode .
            $grossAmount .
            config('services.midtrans.server_key')
        );

        if (!hash_equals($expectedSignature, $signatureKey)) {
            Log::warning(
                'Invalid Midtrans signature.',
                [
                    'order_id' => $orderId,
                ]
            );

            return response()->json([
                'message' => 'Invalid signature.',
            ], 403);
        }

        /*
         * Cari order berdasarkan order_number.
         */
        $order = Order::where(
            'order_number',
            $orderId
        )->first();

        if (!$order) {
            return response()->json([
                'message' => 'Order not found.',
            ], 404);
        }

        /*
         * Pastikan nilai pembayaran yang dikirim
         * Midtrans sama dengan total order kita.
         */
        if ((float) $grossAmount !== (float) $order->total) {
            Log::warning(
                'Midtrans gross amount mismatch.',
                [
                    'order_id' => $orderId,
                    'midtrans_amount' => $grossAmount,
                    'order_amount' => $order->total,
                ]
            );

            return response()->json([
                'message' => 'Gross amount mismatch.',
            ], 400);
        }

        DB::transaction(function () use (
            $order,
            $payload,
            $statusCode,
            $transactionStatus,
            $fraudStatus
        ) {
            /*
             * Lock order agar notification yang datang
             * bersamaan tidak memproses stok dua kali.
             */
            $order = Order::whereKey($order->id)
                ->lockForUpdate()
                ->first();

            $payment = Payment::where(
                'order_id',
                $order->id
            )
                ->lockForUpdate()
                ->first();

            if (!$payment) {
                $payment = new Payment([
                    'order_id' => $order->id,
                    'gross_amount' => $order->total,
                ]);
            }

            /*
             * Status sebelumnya.
             */
            $previousStatus = $payment->transaction_status;

            /*
             * Update data payment.
             */
            $payment->fill([
                'transaction_id' => $payload['transaction_id'] ?? null,
                'payment_type' => $payload['payment_type'] ?? null,
                'transaction_status' => $transactionStatus,
                'fraud_status' => $fraudStatus,
                'status_code' => $statusCode,
            ]);

            /*
             * Payment berhasil.
             */
            $isSuccessful =
                $transactionStatus === 'settlement' ||
                (
                    $transactionStatus === 'capture' &&
                    $fraudStatus === 'accept'
                );

            if ($isSuccessful) {
                $payment->paid_at ??= now();

                $payment->save();

                $order->update([
                    'status' => 'processing',
                ]);

                return;
            }

            /*
             * Payment gagal / expired / dibatalkan.
             */
            $isFailed = in_array(
                $transactionStatus,
                [
                    'cancel',
                    'deny',
                    'expire',
                    'failure',
                ],
                true
            );

            if ($isFailed) {

                /*
                 * Hanya kembalikan stok apabila sebelumnya
                 * transaksi belum berada pada status gagal.
                 *
                 * Ini membuat webhook idempotent.
                 */
                $wasAlreadyFailed = in_array(
                    $previousStatus,
                    [
                        'cancel',
                        'deny',
                        'expire',
                        'failure',
                    ],
                    true
                );

                if (!$wasAlreadyFailed) {

                    $order->load('items');

                    foreach ($order->items as $item) {

                        $product = \App\Models\Product::where(
                            'id',
                            $item->product_id
                        )
                            ->lockForUpdate()
                            ->first();

                        if ($product) {
                            $product->increment(
                                'stock',
                                $item->quantity
                            );
                        }
                    }
                }

                $payment->save();

                $order->update([
                    'status' => 'cancelled',
                ]);

                return;
            }

            /*
             * Pending.
             */
            if ($transactionStatus === 'pending') {

                $payment->save();

                $order->update([
                    'status' => 'pending',
                ]);

                return;
            }

            /*
             * Status lain tetap disimpan tetapi tidak
             * mengubah status order secara agresif.
             */
            $payment->save();
        });

        return response()->json([
            'message' => 'Notification processed.',
        ], 200);
    }
}

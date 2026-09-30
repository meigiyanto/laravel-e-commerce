<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\Request;
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

        if (!$orderId || !$statusCode || !$grossAmount || !$signatureKey) {
            return response()->json([
                'message' => 'Invalid notification.',
            ], 400);
        }

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
                ['order_id' => $orderId]
            );

            return response()->json([
                'message' => 'Invalid signature.',
            ], 403);
        }

        $order = Order::where(
            'order_number',
            $orderId
        )->first();

        if (!$order) {
            return response()->json([
                'message' => 'Order not found.',
            ], 404);
        }

        $payment = $order->payment;

        if (!$payment) {
            $payment = new Payment([
                'order_id' => $order->id,
                'gross_amount' => $order->total,
            ]);
        }

        $payment->fill([
            'transaction_id' => $payload['transaction_id'] ?? null,
            'payment_type' => $payload['payment_type'] ?? null,
            'transaction_status' =>
                $payload['transaction_status'] ?? 'pending',
            'fraud_status' => $payload['fraud_status'] ?? null,
            'status_code' => $statusCode,
        ]);

        $payment->save();

        $transactionStatus =
            $payload['transaction_status'] ?? null;

        $fraudStatus =
            $payload['fraud_status'] ?? null;

        if (
            $transactionStatus === 'settlement' ||
            (
                $transactionStatus === 'capture' &&
                $fraudStatus === 'accept'
            )
        ) {
            $payment->update([
                'paid_at' => now(),
            ]);

            $order->update([
                'status' => 'processing',
            ]);
        }

        if (
            $transactionStatus === 'cancel' ||
            $transactionStatus === 'deny' ||
            $transactionStatus === 'expire'
        ) {
            $order->update([
                'status' => 'cancelled',
            ]);
        }

        return response()->json([
            'message' => 'Notification processed.',
        ]);
    }
}

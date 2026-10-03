<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Payment;

class OrderPaymentService
{
    public function createPendingPayment(
        Order $order,
        string $paymentMethod
    ): Payment {
        return Payment::create([
            'order_id' => $order->id,
            'provider' => $paymentMethod,
            'currency' => 'IDR',
            'status' => 'pending',
            'payment_method' => $paymentMethod,
            'payment_type' => match ($paymentMethod) {
                'stripe' => 'card',
                'midtrans' => 'midtrans_snap',
                'cod' => 'cash_on_delivery',
            },
            'transaction_status' => 'pending',
            'gross_amount' => $order->total,
        ]);
    }
}

<?php

namespace App\Services;

use App\Models\Order;
use Stripe\PaymentIntent;
use Stripe\Stripe;

class StripePaymentService
{
    public function __construct()
    {
        Stripe::setApiKey(
            config('services.stripe.secret')
        );
    }

    public function createPaymentIntent(
        Order $order
    ): PaymentIntent {
        return PaymentIntent::create([
            'amount' => $this->amount($order),
            'currency' => 'idr',
            'payment_method_types' => ['card'],
            'description' => "MeiStore order {$order->order_number}",
            'metadata' => [
                'order_id' => (string) $order->id,
                'order_number' => $order->order_number,
            ],
            'receipt_email' => $order->user?->email,
        ]);
    }

    public function retrieve(
        string $paymentIntentId
    ): PaymentIntent {
        return PaymentIntent::retrieve(
            $paymentIntentId
        );
    }

    public function amount(Order $order): int
    {
        return (int) round(
            (float) $order->total * 100
        );
    }
}

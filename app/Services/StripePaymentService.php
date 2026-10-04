<?php

namespace App\Services;

use Stripe\ApiRequestor;
use Stripe\HttpClient\CurlClient;
use App\Models\Order;
use Stripe\PaymentIntent;
use Stripe\Refund;
use Stripe\Stripe;

class StripePaymentService
{
    public function __construct()
    {
        Stripe::setApiKey(
            config('services.stripe.secret')
        );

        $timeout = (int) config(
            'services.refund.provider_timeout_seconds',
            80
        );

        $client = new CurlClient();

        $client->setTimeout($timeout);
        $client->setConnectTimeout(
            min($timeout, CurlClient::DEFAULT_CONNECT_TIMEOUT)
        );

        ApiRequestor::setHttpClient($client);
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

    public function refund(
        string $paymentIntentId,
        int $amount,
        string $reason = 'requested_by_customer',
        ?string $idempotencyKey = null
    ): Refund {
        $options = $idempotencyKey !== null
            ? ['idempotency_key' => $idempotencyKey]
            : [];

        return Refund::create(
            [
                'payment_intent' => $paymentIntentId,
                'amount' => $amount,
                'reason' => $reason,
            ],
            $options
        );
    }
}

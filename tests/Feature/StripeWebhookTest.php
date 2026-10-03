<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Stripe\Stripe;
use Stripe\Webhook;
use Tests\TestCase;

class StripeWebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_stripe_webhook_completes_pending_payment_and_order(): void
    {
        $user = User::factory()->create();

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'pending',
            'total' => 100000,
        ]);

        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'provider' => 'stripe',
            'status' => 'pending',
            'gross_amount' => 100000,
            'stripe_payment_intent_id' => 'pi_test_success_123',
        ]);

        $payload = [
            'id' => 'evt_test_success_123',
            'object' => 'event',
            'type' => 'payment_intent.succeeded',
            'data' => [
                'object' => [
                    'id' => 'pi_test_success_123',
                    'object' => 'payment_intent',
                    'amount' => 100000,
                    'currency' => 'idr',
                    'status' => 'succeeded',
                ],
            ],
        ];

        $payload = json_encode($payload);

        $secret = 'whsec_test_secret';

        Config::set('services.stripe.webhook_secret', $secret);

        $signature = Webhook::generateTestHeaderString(
            $payload,
            $secret
        );

        $response = $this->call(
            'POST',
            route('stripe.webhook'),
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_STRIPE_SIGNATURE' => $signature,
            ],
            $payload
        );

        $response->assertSuccessful();

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => 'succeeded',
            'stripe_payment_intent_id' => 'pi_test_success_123',
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'completed',
        ]);
    }

    public function test_stripe_webhook_rejects_invalid_signature(): void
    {
        $user = User::factory()->create();

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'pending',
            'total' => 100000,
        ]);

        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'provider' => 'stripe',
            'status' => 'pending',
            'gross_amount' => 100000,
            'stripe_payment_intent_id' => 'pi_test_invalid_signature',
        ]);

        $payload = json_encode([
            'id' => 'evt_invalid_signature',
            'object' => 'event',
            'type' => 'payment_intent.succeeded',
            'data' => [
                'object' => [
                    'id' => 'pi_test_invalid_signature',
                    'amount' => 100000,
                    'currency' => 'idr',
                    'status' => 'succeeded',
                ],
            ],
        ]);

        Config::set(
            'services.stripe.webhook_secret',
            'whsec_test_secret'
        );

        $response = $this->call(
            'POST',
            route('stripe.webhook'),
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_STRIPE_SIGNATURE' => 'invalid-signature',
            ],
            $payload
        );

        $response->assertStatus(400);

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => 'pending',
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'pending',
        ]);
    }

    public function test_stripe_webhook_does_not_downgrade_succeeded_payment(): void
    {
        $user = User::factory()->create();

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'completed',
            'total' => 100000,
        ]);

        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'provider' => 'stripe',
            'status' => 'succeeded',
            'gross_amount' => 100000,
            'stripe_payment_intent_id' => 'pi_already_succeeded',
        ]);

        $payload = json_encode([
            'id' => 'evt_payment_failed_after_success',
            'object' => 'event',
            'type' => 'payment_intent.payment_failed',
            'data' => [
                'object' => [
                    'id' => 'pi_already_succeeded',
                    'amount' => 100000,
                    'currency' => 'idr',
                    'status' => 'requires_payment_method',
                ],
            ],
        ]);

        $secret = 'whsec_test_secret';

        Config::set('services.stripe.webhook_secret', $secret);

        $signature = Webhook::generateTestHeaderString(
            $payload,
            $secret
        );

        $response = $this->call(
            'POST',
            route('stripe.webhook'),
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_STRIPE_SIGNATURE' => $signature,
            ],
            $payload
        );

        $response->assertSuccessful();

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => 'succeeded',
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'completed',
        ]);
    }
}

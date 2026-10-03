<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Stripe\Webhook;
use Stripe\WebhookSignature;
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
                    'amount' => 10000000,
                    'currency' => 'idr',
                    'status' => 'succeeded',
                ],
            ],
        ];

        $payload = json_encode($payload);

        $secret = 'whsec_test_secret';

        Config::set('services.stripe.webhook_secret', $secret);

        $signature = WebhookSignature::generateSignatureHeader(
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
            'stripe_payment_intent_id' => 'pi_test_success_123',
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

        $signature = WebhookSignature::generateSignatureHeader(
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

    public function test_stripe_webhook_marks_pending_payment_as_failed(): void
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
            'stripe_payment_intent_id' => 'pi_failed_123',
        ]);

        $payload = json_encode([
            'id' => 'evt_failed_123',
            'object' => 'event',
            'type' => 'payment_intent.payment_failed',
            'data' => [
                'object' => [
                    'id' => 'pi_failed_123',
                    'object' => 'payment_intent',
                    'amount' => 10000000,
                    'currency' => 'idr',
                    'status' => 'requires_payment_method',
                    'last_payment_error' => null,
                ],
            ],
        ]);

        $secret = 'whsec_test_secret';

        Config::set(
            'services.stripe.webhook_secret',
            $secret
        );

        $signature = WebhookSignature::generateSignatureHeader(
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
            'status' => 'failed',
            'transaction_status' => 'failed',
            'transaction_id' => 'pi_failed_123',
            'reference_id' => 'pi_failed_123',
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'pending',
        ]);
    }

    public function test_stripe_webhook_marks_pending_payment_as_canceled(): void
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
            'stripe_payment_intent_id' => 'pi_canceled_123',
        ]);

        $payload = json_encode([
            'id' => 'evt_canceled_123',
            'object' => 'event',
            'type' => 'payment_intent.canceled',
            'data' => [
                'object' => [
                    'id' => 'pi_canceled_123',
                    'object' => 'payment_intent',
                    'amount' => 10000000,
                    'currency' => 'idr',
                    'status' => 'canceled',
                ],
            ],
        ]);

        $secret = 'whsec_test_secret';

        Config::set(
            'services.stripe.webhook_secret',
            $secret
        );

        $signature = WebhookSignature::generateSignatureHeader(
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
            'status' => 'canceled',
            'transaction_status' => 'canceled',
            'transaction_id' => 'pi_canceled_123',
            'reference_id' => 'pi_canceled_123',
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'pending',
        ]);
    }

    public function test_stripe_webhook_does_not_downgrade_succeeded_payment_when_canceled(): void
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
            'stripe_payment_intent_id' => 'pi_succeeded_canceled',
        ]);

        $payload = json_encode([
            'id' => 'evt_succeeded_canceled',
            'object' => 'event',
            'type' => 'payment_intent.canceled',
            'data' => [
                'object' => [
                    'id' => 'pi_succeeded_canceled',
                    'object' => 'payment_intent',
                    'amount' => 10000000,
                    'currency' => 'idr',
                    'status' => 'canceled',
                ],
            ],
        ]);

        $secret = 'whsec_test_secret';

        Config::set(
            'services.stripe.webhook_secret',
            $secret
        );

        $signature = WebhookSignature::generateSignatureHeader(
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

    public function test_stripe_webhook_does_not_complete_order_when_amount_does_not_match(): void
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
            'stripe_payment_intent_id' => 'pi_amount_mismatch',
        ]);

        $payload = json_encode([
            'id' => 'evt_amount_mismatch',
            'object' => 'event',
            'type' => 'payment_intent.succeeded',
            'data' => [
                'object' => [
                    'id' => 'pi_amount_mismatch',
                    'object' => 'payment_intent',
                    // Order = 100000, Stripe = 90000
                    'amount' => 9000000,
                    'currency' => 'idr',
                    'status' => 'succeeded',
                ],
            ],
        ]);

        $secret = 'whsec_test_secret';

        Config::set(
            'services.stripe.webhook_secret',
            $secret
        );

        $signature = WebhookSignature::generateSignatureHeader(
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

        $response->assertStatus(422);

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => 'pending',
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'pending',
        ]);
    }

    public function test_stripe_webhook_completes_order_when_amount_matches(): void
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
            'stripe_payment_intent_id' => 'pi_amount_match',
        ]);

        $payload = json_encode([
            'id' => 'evt_amount_match',
            'object' => 'event',
            'type' => 'payment_intent.succeeded',
            'data' => [
                'object' => [
                    'id' => 'pi_amount_match',
                    'object' => 'payment_intent',
                    // Nominal Stripe harus sama dengan gross_amount payment.
                    'amount' => 10000000,
                    'currency' => 'idr',
                    'status' => 'succeeded',
                ],
            ],
        ]);

        $secret = 'whsec_test_secret';

        Config::set(
            'services.stripe.webhook_secret',
            $secret
        );

        $signature = WebhookSignature::generateSignatureHeader(
            $payload,
            $secret
        );

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'gross_amount' => 100000,
            'stripe_payment_intent_id' => 'pi_amount_match',
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'total' => 100000,
            'status' => 'pending',
        ]);

        $this->assertSame(
            100000.0,
            (float) $payment->fresh()->gross_amount
        );

        $this->assertSame(
            100000,
            10000000 / 100
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

        dump([
            'status' => $response->status(),
            'body' => $response->getContent(),
        ]);
        $response->assertStatus(200);

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => 'succeeded',
            'transaction_status' => 'succeeded',
            'transaction_id' => 'pi_amount_match',
            'reference_id' => 'pi_amount_match',
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'completed',
        ]);
    }

    public function test_stripe_webhook_ignores_unknown_payment_intent(): void
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
            'stripe_payment_intent_id' => 'pi_known_payment',
        ]);

        $payload = json_encode([
            'id' => 'evt_unknown_payment',
            'object' => 'event',
            'type' => 'payment_intent.succeeded',
            'data' => [
                'object' => [
                    'id' => 'pi_unknown_payment',
                    'object' => 'payment_intent',
                    'amount' => 10000000,
                    'currency' => 'idr',
                    'status' => 'succeeded',
                ],
            ],
        ]);

        $secret = 'whsec_test_secret';

        Config::set(
            'services.stripe.webhook_secret',
            $secret
        );

        $signature = WebhookSignature::generateSignatureHeader(
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
            'status' => 'pending',
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'pending',
        ]);
    }

    public function test_stripe_webhook_does_not_modify_non_stripe_payment(): void
    {
        $user = User::factory()->create();

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'pending',
            'total' => 100000,
        ]);

        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'provider' => 'midtrans',
            'status' => 'pending',
            'gross_amount' => 100000,
            'stripe_payment_intent_id' => null,
        ]);

        $payload = json_encode([
            'id' => 'evt_non_stripe_payment',
            'object' => 'event',
            'type' => 'payment_intent.succeeded',
            'data' => [
                'object' => [
                    'id' => 'pi_non_stripe_payment',
                    'object' => 'payment_intent',
                    'amount' => 10000000,
                    'currency' => 'idr',
                    'status' => 'succeeded',
                ],
            ],
        ]);

        $secret = 'whsec_test_secret';

        Config::set(
            'services.stripe.webhook_secret',
            $secret
        );

        $signature = WebhookSignature::generateSignatureHeader(
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
            'provider' => 'midtrans',
            'status' => 'pending',
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'pending',
        ]);
    }

    public function test_stripe_webhook_can_complete_failed_payment_when_payment_succeeds(): void
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
            'status' => 'failed',
            'gross_amount' => 100000,
            'stripe_payment_intent_id' => 'pi_failed_then_succeeded',
        ]);

        $payload = json_encode([
            'id' => 'evt_failed_then_succeeded',
            'object' => 'event',
            'type' => 'payment_intent.succeeded',
            'data' => [
                'object' => [
                    'id' => 'pi_failed_then_succeeded',
                    'object' => 'payment_intent',
                    'amount' => 10000000,
                    'currency' => 'idr',
                    'status' => 'succeeded',
                ],
            ],
        ]);

        $secret = 'whsec_test_secret';

        Config::set(
            'services.stripe.webhook_secret',
            $secret
        );

        $signature = WebhookSignature::generateSignatureHeader(
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
            'transaction_status' => 'succeeded',
            'transaction_id' => 'pi_failed_then_succeeded',
            'reference_id' => 'pi_failed_then_succeeded',
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'completed',
        ]);
    }

    public function test_stripe_webhook_does_not_complete_canceled_payment_when_payment_succeeds(): void
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
            'status' => 'canceled',
            'gross_amount' => 100000,
            'stripe_payment_intent_id' => 'pi_canceled_then_succeeded',
        ]);

        $payload = json_encode([
            'id' => 'evt_canceled_then_succeeded',
            'object' => 'event',
            'type' => 'payment_intent.succeeded',
            'data' => [
                'object' => [
                    'id' => 'pi_canceled_then_succeeded',
                    'object' => 'payment_intent',
                    'amount' => 10000000,
                    'currency' => 'idr',
                    'status' => 'succeeded',
                ],
            ],
        ]);

        $secret = 'whsec_test_secret';

        Config::set(
            'services.stripe.webhook_secret',
            $secret
        );

        $signature = WebhookSignature::generateSignatureHeader(
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
            'status' => 'canceled',
            'transaction_status' => 'pending',
            'transaction_id' => null,
            'reference_id' => null,
        ]);

       $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'pending',
        ]);
    }

    public function test_stripe_webhook_does_not_downgrade_canceled_payment_when_failed(): void
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
            'status' => 'canceled',
            'gross_amount' => 100000,
            'stripe_payment_intent_id' => 'pi_canceled_then_failed',
        ]);

        $payload = json_encode([
            'id' => 'evt_canceled_then_failed',
            'object' => 'event',
            'type' => 'payment_intent.payment_failed',
            'data' => [
                'object' => [
                    'id' => 'pi_canceled_then_failed',
                    'object' => 'payment_intent',
                    'amount' => 10000000,
                    'currency' => 'idr',
                    'status' => 'requires_payment_method',
                    'last_payment_error' => null,
                ],
            ],
        ]);

        $secret = 'whsec_test_secret';

        Config::set(
            'services.stripe.webhook_secret',
            $secret
        );

        $signature = WebhookSignature::generateSignatureHeader(
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

        // $this->assertDatabaseHas('payments', [
        //     'id' => $payment->id,
        //     'status' => 'canceled',
        //     'transaction_status' => 'pending',
        //     'transaction_id' => null,
        //     'reference_id' => null,
        // ]);
        
        // $this->assertDatabaseHas('orders', [
        //     'id' => $order->id,
        //     'status' => 'pending',
        // ]);
        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => 'canceled',
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'pending',
        ]);
    }

    public function test_stripe_webhook_does_not_change_failed_payment_when_canceled(): void
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
            'status' => 'failed',
            'gross_amount' => 100000,
            'stripe_payment_intent_id' => 'pi_failed_then_canceled',
        ]);

        $payload = json_encode([
            'id' => 'evt_failed_then_canceled',
            'object' => 'event',
            'type' => 'payment_intent.canceled',
            'data' => [
                'object' => [
                    'id' => 'pi_failed_then_canceled',
                    'object' => 'payment_intent',
                    'amount' => 10000000,
                    'currency' => 'idr',
                    'status' => 'canceled',
                ],
            ],
        ]);

        $secret = 'whsec_test_secret';

        Config::set(
            'services.stripe.webhook_secret',
            $secret
        );

        $signature = WebhookSignature::generateSignatureHeader(
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
            'status' => 'failed',
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'pending',
        ]);
    }

    public function test_stripe_webhook_is_idempotent_for_repeated_succeeded_event(): void
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
            'stripe_payment_intent_id' => 'pi_repeated_success',
        ]);

        $payload = json_encode([
            'id' => 'evt_repeated_success',
            'object' => 'event',
            'type' => 'payment_intent.succeeded',
            'data' => [
                'object' => [
                    'id' => 'pi_repeated_success',
                    'object' => 'payment_intent',
                    'amount' => 10000000,
                    'currency' => 'idr',
                    'status' => 'succeeded',
                ],
            ],
        ]);

        $secret = 'whsec_test_secret';

        Config::set(
            'services.stripe.webhook_secret',
            $secret
        );

        $signature = WebhookSignature::generateSignatureHeader(
            $payload,
            $secret
        );

        $headers = [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_STRIPE_SIGNATURE' => $signature,
        ];

        $firstResponse = $this->call(
            'POST',
            route('stripe.webhook'),
            [],
            [],
            [],
            $headers,
            $payload
        );

        $firstResponse->assertSuccessful();

        $payment->refresh();

        $firstPaidAt = $payment->paid_at;
        $firstUpdatedAt = $payment->updated_at;

        $secondResponse = $this->call(
            'POST',
            route('stripe.webhook'),
            [],
            [],
            [],
            $headers,
            $payload
        );

        $secondResponse->assertSuccessful();

        $payment->refresh();

        $this->assertSame('succeeded', $payment->status);
        $this->assertSame('succeeded', $payment->transaction_status);
        $this->assertSame(
            $firstPaidAt?->toISOString(),
            $payment->paid_at?->toISOString()
        );
        $this->assertSame(
            $firstUpdatedAt?->toISOString(),
            $payment->updated_at?->toISOString()
        );

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'completed',
        ]);
    }

    public function test_stripe_webhook_is_idempotent_for_repeated_failed_event(): void
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
            'stripe_payment_intent_id' => 'pi_repeated_failed',
        ]);

        $payload = json_encode([
            'id' => 'evt_repeated_failed',
            'object' => 'event',
            'type' => 'payment_intent.payment_failed',
            'data' => [
                'object' => [
                    'id' => 'pi_repeated_failed',
                    'object' => 'payment_intent',
                    'amount' => 10000000,
                    'currency' => 'idr',
                    'status' => 'requires_payment_method',
                    'last_payment_error' => null,
                ],
            ],
        ]);

        $secret = 'whsec_test_secret';

        Config::set(
            'services.stripe.webhook_secret',
            $secret
        );

        $signature = WebhookSignature::generateSignatureHeader(
            $payload,
            $secret
        );

        $headers = [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_STRIPE_SIGNATURE' => $signature,
        ];

        $firstResponse = $this->call(
            'POST',
            route('stripe.webhook'),
            [],
            [],
            [],
            $headers,
            $payload
        );

        $firstResponse->assertSuccessful();

        $payment->refresh();

        $firstUpdatedAt = $payment->updated_at;
        $firstMetadata = $payment->metadata;

        $secondResponse = $this->call(
            'POST',
            route('stripe.webhook'),
            [],
            [],
            [],
            $headers,
            $payload
        );

        $secondResponse->assertSuccessful();

        $payment->refresh();

        $this->assertSame('failed', $payment->status);
        $this->assertSame('failed', $payment->transaction_status);
        $this->assertSame(
            $firstUpdatedAt->toISOString(),
            $payment->updated_at->toISOString()
        );
        $this->assertSame($firstMetadata, $payment->metadata);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'pending',
        ]);
    }

    public function test_stripe_webhook_is_idempotent_for_repeated_canceled_event(): void
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
            'stripe_payment_intent_id' => 'pi_repeated_canceled',
        ]);

        $payload = json_encode([
            'id' => 'evt_repeated_canceled',
            'object' => 'event',
            'type' => 'payment_intent.canceled',
            'data' => [
                'object' => [
                    'id' => 'pi_repeated_canceled',
                    'object' => 'payment_intent',
                    'amount' => 10000000,
                    'currency' => 'idr',
                    'status' => 'canceled',
                ],
            ],
        ]);

        $secret = 'whsec_test_secret';

        Config::set(
            'services.stripe.webhook_secret',
            $secret
        );

        $signature = WebhookSignature::generateSignatureHeader(
            $payload,
            $secret
        );

        $headers = [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_STRIPE_SIGNATURE' => $signature,
        ];

        $firstResponse = $this->call(
            'POST',
            route('stripe.webhook'),
            [],
            [],
            [],
            $headers,
            $payload
        );

        $firstResponse->assertSuccessful();

        $payment->refresh();

        $firstUpdatedAt = $payment->updated_at;
        $firstMetadata = $payment->metadata;

        $secondResponse = $this->call(
            'POST',
            route('stripe.webhook'),
            [],
            [],
            [],
            $headers,
            $payload
        );

        $secondResponse->assertSuccessful();

        $payment->refresh();

        $this->assertSame('canceled', $payment->status);
        $this->assertSame('canceled', $payment->transaction_status);
        $this->assertSame(
            $firstUpdatedAt->toISOString(),
            $payment->updated_at->toISOString()
        );
        $this->assertSame($firstMetadata, $payment->metadata);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'pending',
        ]);
    }

    public function test_stripe_webhook_does_not_complete_canceled_payment_when_succeeded_event_arrives_late(): void
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
            'status' => 'canceled',
            'gross_amount' => 100000,
            'stripe_payment_intent_id' => 'pi_late_succeeded',
        ]);

        $payload = json_encode([
            'id' => 'evt_late_succeeded',
            'object' => 'event',
            'type' => 'payment_intent.succeeded',
            'data' => [
                'object' => [
                    'id' => 'pi_late_succeeded',
                    'object' => 'payment_intent',
                    'amount' => 10000000,
                    'currency' => 'idr',
                    'status' => 'succeeded',
                ],
            ],
        ]);

        $secret = 'whsec_test_secret';

        Config::set(
            'services.stripe.webhook_secret',
            $secret
        );

        $signature = WebhookSignature::generateSignatureHeader(
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
            'status' => 'canceled',
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'pending',
        ]);
    }

    public function test_stripe_webhook_does_not_modify_payment_with_non_stripe_provider(): void
    {
        $user = User::factory()->create();

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'pending',
            'total' => 100000,
        ]);

        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'provider' => 'midtrans',
            'status' => 'pending',
            'gross_amount' => 100000,
            'stripe_payment_intent_id' => 'pi_wrong_provider',
        ]);

        $payload = json_encode([
            'id' => 'evt_wrong_provider',
            'object' => 'event',
            'type' => 'payment_intent.succeeded',
            'data' => [
                'object' => [
                    'id' => 'pi_wrong_provider',
                    'object' => 'payment_intent',
                    'amount' => 10000000,
                    'currency' => 'idr',
                    'status' => 'succeeded',
                ],
            ],
        ]);

        $secret = 'whsec_test_secret';

        Config::set(
            'services.stripe.webhook_secret',
            $secret
        );

        $signature = WebhookSignature::generateSignatureHeader(
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
            'provider' => 'midtrans',
            'status' => 'pending',
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'pending',
        ]);
    }

    public function test_stripe_webhook_does_not_complete_payment_when_currency_does_not_match(): void
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
            'currency' => 'idr',
            'gross_amount' => 100000,
            'stripe_payment_intent_id' => 'pi_currency_mismatch',
        ]);

        $payload = json_encode([
            'id' => 'evt_currency_mismatch',
            'object' => 'event',
            'type' => 'payment_intent.succeeded',
            'data' => [
                'object' => [
                    'id' => 'pi_currency_mismatch',
                    'object' => 'payment_intent',
                    'amount' => 10000000,
                    'currency' => 'usd',
                    'status' => 'succeeded',
                ],
            ],
        ]);

        $secret = 'whsec_test_secret';

        Config::set(
            'services.stripe.webhook_secret',
            $secret
        );

        $signature = WebhookSignature::generateSignatureHeader(
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

        $response->assertStatus(422);

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => 'pending',
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'pending',
        ]);
    }

    public function test_stripe_webhook_does_not_complete_payment_when_order_is_already_canceled(): void
    {
        $user = User::factory()->create();

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'canceled',
            'total' => 100000,
        ]);

        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'provider' => 'stripe',
            'status' => 'pending',
            'gross_amount' => 100000,
            'stripe_payment_intent_id' => 'pi_canceled_order',
        ]);

        $payload = json_encode([
            'id' => 'evt_canceled_order',
            'object' => 'event',
            'type' => 'payment_intent.succeeded',
            'data' => [
                'object' => [
                    'id' => 'pi_canceled_order',
                    'object' => 'payment_intent',
                    'amount' => 10000000,
                    'currency' => 'idr',
                    'status' => 'succeeded',
                ],
            ],
        ]);

        $secret = 'whsec_test_secret';

        Config::set(
            'services.stripe.webhook_secret',
            $secret
        );

        $signature = WebhookSignature::generateSignatureHeader(
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
            'status' => 'pending',
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'canceled',
        ]);
    }

    public function test_stripe_webhook_does_not_change_completed_order_to_invalid_state(): void
    {
        $user = User::factory()->create();

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'completed',
        ]);

        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'provider' => 'stripe',
            'status' => 'pending',
            'transaction_status' => 'pending',
            'gross_amount' => 100000,
            'currency' => 'idr',
            'stripe_payment_intent_id' => 'pi_completed_order',
        ]);

        $payload = json_encode([
            'id' => 'evt_completed_order',
            'object' => 'event',
            'type' => 'payment_intent.succeeded',
            'data' => [
                'object' => [
                    'id' => 'pi_completed_order',
                    'object' => 'payment_intent',
                    'amount' => 10000000,
                    'currency' => 'idr',
                    'status' => 'succeeded',
                ],
            ],
        ]);

        $secret = 'whsec_test_secret';

        Config::set(
            'services.stripe.webhook_secret',
            $secret
        );

        $signature = WebhookSignature::generateSignatureHeader(
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

    public function test_stripe_webhook_does_not_complete_failed_order(): void
    {
        $user = User::factory()->create();

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'failed',
        ]);

        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'provider' => 'stripe',
            'status' => 'pending',
            'transaction_status' => 'pending',
            'gross_amount' => 100000,
            'currency' => 'idr',
            'stripe_payment_intent_id' => 'pi_failed_order',
        ]);

        $payload = json_encode([
            'id' => 'evt_failed_order',
            'object' => 'event',
            'type' => 'payment_intent.succeeded',
            'data' => [
                'object' => [
                    'id' => 'pi_failed_order',
                    'object' => 'payment_intent',
                    'amount' => 10000000,
                    'currency' => 'idr',
                    'status' => 'succeeded',
                ],
            ],
        ]);

        $secret = 'whsec_test_secret';

        Config::set(
            'services.stripe.webhook_secret',
            $secret
        );

        $signature = WebhookSignature::generateSignatureHeader(
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
            'status' => 'pending',
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'failed',
        ]);
    }

    public function test_stripe_webhook_does_not_complete_refunded_order(): void
    {
        $user = User::factory()->create();

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'refunded',
        ]);

        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'provider' => 'stripe',
            'status' => 'pending',
            'transaction_status' => 'pending',
            'gross_amount' => 100000,
            'currency' => 'idr',
            'stripe_payment_intent_id' => 'pi_refunded_order',
        ]);

        $payload = json_encode([
            'id' => 'evt_refunded_order',
            'object' => 'event',
            'type' => 'payment_intent.succeeded',
            'data' => [
                'object' => [
                    'id' => 'pi_refunded_order',
                    'object' => 'payment_intent',
                    'amount' => 10000000,
                    'currency' => 'idr',
                    'status' => 'succeeded',
                ],
            ],
        ]);

        $secret = 'whsec_test_secret';

        Config::set(
            'services.stripe.webhook_secret',
            $secret
        );

        $signature = WebhookSignature::generateSignatureHeader(
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
            'status' => 'pending',
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'refunded',
        ]);
    }

    public function test_stripe_webhook_completes_processing_order_when_payment_succeeds(): void
    {
        $user = User::factory()->create();

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'processing',
        ]);

        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'provider' => 'stripe',
            'status' => 'pending',
            'transaction_status' => 'pending',
            'gross_amount' => 100000,
            'currency' => 'idr',
            'stripe_payment_intent_id' => 'pi_processing_order',
        ]);

        $payload = json_encode([
            'id' => 'evt_processing_order',
            'object' => 'event',
            'type' => 'payment_intent.succeeded',
            'data' => [
                'object' => [
                    'id' => 'pi_processing_order',
                    'object' => 'payment_intent',
                    'amount' => 10000000,
                    'currency' => 'idr',
                    'status' => 'succeeded',
                ],
            ],
        ]);

        $secret = 'whsec_test_secret';

        Config::set(
            'services.stripe.webhook_secret',
            $secret
        );

        $signature = WebhookSignature::generateSignatureHeader(
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

    public function test_stripe_webhook_does_not_fail_completed_order(): void
    {
        $user = User::factory()->create();

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'completed',
        ]);

        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'provider' => 'stripe',
            'status' => 'pending',
            'transaction_status' => 'pending',
            'gross_amount' => 100000,
            'currency' => 'idr',
            'stripe_payment_intent_id' => 'pi_completed_failed',
        ]);

        $payload = json_encode([
            'id' => 'evt_completed_failed',
            'object' => 'event',
            'type' => 'payment_intent.payment_failed',
            'data' => [
                'object' => [
                    'id' => 'pi_completed_failed',
                    'object' => 'payment_intent',
                    'amount' => 10000000,
                    'currency' => 'idr',
                    'status' => 'requires_payment_method',
                    'last_payment_error' => [
                        'code' => 'card_declined',
                        'message' => 'Your card was declined.',
                    ],
                ],
            ],
        ]);

        $secret = 'whsec_test_secret';

        Config::set(
            'services.stripe.webhook_secret',
            $secret
        );

        $signature = WebhookSignature::generateSignatureHeader(
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
            'status' => 'pending',
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'completed',
        ]);
    }

    public function test_stripe_webhook_does_not_fail_canceled_order_payment(): void
    {
        $user = User::factory()->create();

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'canceled',
        ]);

        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'provider' => 'stripe',
            'status' => 'pending',
            'transaction_status' => 'pending',
            'gross_amount' => 100000,
            'currency' => 'idr',
            'stripe_payment_intent_id' => 'pi_canceled_failed',
        ]);

        $payload = json_encode([
            'id' => 'evt_canceled_failed',
            'object' => 'event',
            'type' => 'payment_intent.payment_failed',
            'data' => [
                'object' => [
                    'id' => 'pi_canceled_failed',
                    'object' => 'payment_intent',
                    'amount' => 10000000,
                    'currency' => 'idr',
                    'status' => 'requires_payment_method',
                    'last_payment_error' => [
                        'code' => 'card_declined',
                        'message' => 'Your card was declined.',
                    ],
                ],
            ],
        ]);

        $secret = 'whsec_test_secret';

        Config::set(
            'services.stripe.webhook_secret',
            $secret
        );

        $signature = WebhookSignature::generateSignatureHeader(
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
            'status' => 'pending',
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'canceled',
        ]);
    }

    public function test_stripe_webhook_does_not_fail_refunded_order_payment(): void
    {
        $user = User::factory()->create();

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'refunded',
        ]);

        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'provider' => 'stripe',
            'status' => 'pending',
            'transaction_status' => 'pending',
            'gross_amount' => 100000,
            'currency' => 'idr',
            'stripe_payment_intent_id' => 'pi_refunded_failed',
        ]);

        $payload = json_encode([
            'id' => 'evt_refunded_failed',
            'object' => 'event',
            'type' => 'payment_intent.payment_failed',
            'data' => [
                'object' => [
                    'id' => 'pi_refunded_failed',
                    'object' => 'payment_intent',
                    'amount' => 10000000,
                    'currency' => 'idr',
                    'status' => 'requires_payment_method',
                    'last_payment_error' => [
                        'code' => 'card_declined',
                        'message' => 'Your card was declined.',
                    ],
                ],
            ],
        ]);

        $secret = 'whsec_test_secret';

        Config::set(
            'services.stripe.webhook_secret',
            $secret
        );

        $signature = WebhookSignature::generateSignatureHeader(
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
            'status' => 'pending',
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'refunded',
        ]);
    }

    public function test_stripe_webhook_does_not_cancel_completed_order_payment(): void
    {
        $user = User::factory()->create();

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'completed',
        ]);

        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'provider' => 'stripe',
            'status' => 'pending',
            'transaction_status' => 'pending',
            'gross_amount' => 100000,
            'currency' => 'idr',
            'stripe_payment_intent_id' => 'pi_completed_canceled',
        ]);

        $payload = json_encode([
            'id' => 'evt_completed_canceled',
            'object' => 'event',
            'type' => 'payment_intent.canceled',
            'data' => [
                'object' => [
                    'id' => 'pi_completed_canceled',
                    'object' => 'payment_intent',
                    'amount' => 10000000,
                    'currency' => 'idr',
                    'status' => 'canceled',
                ],
            ],
        ]);

        $secret = 'whsec_test_secret';

        Config::set(
            'services.stripe.webhook_secret',
            $secret
        );

        $signature = WebhookSignature::generateSignatureHeader(
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
            'status' => 'pending',
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'completed',
        ]);
    }

    public function test_stripe_webhook_succeeded_is_idempotent(): void
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
            'stripe_payment_intent_id' => 'pi_idempotent_123',
        ]);

        $payload = json_encode([
            'id' => 'evt_idempotent_123',
            'object' => 'event',
            'type' => 'payment_intent.succeeded',
            'data' => [
                'object' => [
                    'id' => 'pi_idempotent_123',
                    'object' => 'payment_intent',
                    'amount' => 10000000,
                    'currency' => 'idr',
                    'status' => 'succeeded',
                ],
            ],
        ]);

        $secret = 'whsec_test_secret';

        Config::set(
            'services.stripe.webhook_secret',
            $secret
        );

        $signature = WebhookSignature::generateSignatureHeader(
            $payload,
            $secret
        );

        // Webhook pertama
        $firstResponse = $this->call(
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

        $firstResponse->assertSuccessful();

        $payment->refresh();
        $order->refresh();

        $this->assertSame('succeeded', $payment->status);
        $this->assertSame('completed', $order->status);

        // Webhook kedua dengan event yang sama
        $secondResponse = $this->call(
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

        $secondResponse->assertSuccessful();

        $payment->refresh();
        $order->refresh();

        $this->assertSame('succeeded', $payment->status);
        $this->assertSame('completed', $order->status);
    }

    public function test_stripe_webhook_does_not_cancel_refunded_order_payment(): void
    {
        $user = User::factory()->create();

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'refunded',
        ]);

        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'provider' => 'stripe',
            'status' => 'pending',
            'transaction_status' => 'pending',
            'gross_amount' => 100000,
            'currency' => 'idr',
            'stripe_payment_intent_id' => 'pi_refunded_canceled',
        ]);

        $payload = json_encode([
            'id' => 'evt_refunded_canceled',
            'object' => 'event',
            'type' => 'payment_intent.canceled',
            'data' => [
                'object' => [
                    'id' => 'pi_refunded_canceled',
                    'object' => 'payment_intent',
                    'amount' => 10000000,
                    'currency' => 'idr',
                    'status' => 'canceled',
                ],
            ],
        ]);

        $secret = 'whsec_test_secret';

        Config::set(
            'services.stripe.webhook_secret',
            $secret
        );

        $signature = WebhookSignature::generateSignatureHeader(
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
            'status' => 'pending',
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'refunded',
        ]);
    }

    public function test_stripe_webhook_does_not_complete_payment_when_succeeded_event_has_invalid_payment_intent_status(): void
    {
        $user = User::factory()->create();

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'pending',
        ]);

        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'provider' => 'stripe',
            'status' => 'pending',
            'transaction_status' => 'pending',
            'gross_amount' => 100000,
            'currency' => 'idr',
            'stripe_payment_intent_id' => 'pi_invalid_success_status',
        ]);

        $payload = json_encode([
            'id' => 'evt_invalid_success_status',
            'object' => 'event',
            'type' => 'payment_intent.succeeded',
            'data' => [
                'object' => [
                    'id' => 'pi_invalid_success_status',
                    'object' => 'payment_intent',
                    'amount' => 10000000,
                    'currency' => 'idr',
                    'status' => 'requires_payment_method',
                ],
            ],
        ]);

        $secret = 'whsec_test_secret';

        Config::set(
            'services.stripe.webhook_secret',
            $secret
        );

        $signature = WebhookSignature::generateSignatureHeader(
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
            'status' => 'pending',
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'pending',
        ]);
    }

    public function test_stripe_webhook_does_not_complete_payment_when_succeeded_event_has_missing_currency(): void
    {
        Config::set(
            'services.stripe.webhook_secret',
            'whsec_test_secret'
        );

        $order = Order::factory()->create([
            'status' => 'pending',
        ]);

        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'provider' => 'stripe',
            'stripe_payment_intent_id' => 'pi_missing_currency',
            'currency' => 'idr',
            'gross_amount' => 100000,
            'status' => 'pending',
        ]);

        $paymentIntent = [
            'id' => 'pi_missing_currency',
            'object' => 'payment_intent',
            'status' => 'succeeded',
            'amount' => 10000000,
            // currency sengaja tidak diberikan
        ];

        $payload = json_encode([
            'id' => 'evt_missing_currency',
            'object' => 'event',
            'type' => 'payment_intent.succeeded',
            'data' => [
                'object' => $paymentIntent,
            ],
        ]);

        $signature = WebhookSignature::generateSignatureHeader(
            $payload,
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
                'HTTP_STRIPE_SIGNATURE' => $signature,
            ],
            $payload
        );

        $response->assertStatus(422);

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => 'pending',
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'pending',
        ]);
    }

    public function test_stripe_webhook_does_not_complete_payment_when_succeeded_event_has_missing_amount(): void
    {
        Config::set(
            'services.stripe.webhook_secret',
            'whsec_test_secret'
        );

        $order = Order::factory()->create([
            'status' => 'pending',
        ]);

        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'provider' => 'stripe',
            'stripe_payment_intent_id' => 'pi_missing_amount',
            'currency' => 'idr',
            'gross_amount' => 100000,
            'status' => 'pending',
        ]);

        $paymentIntent = [
            'id' => 'pi_missing_amount',
            'object' => 'payment_intent',
            'status' => 'succeeded',
            'currency' => 'idr',
            // amount sengaja tidak diberikan
        ];

        $payload = json_encode([
            'id' => 'evt_missing_amount',
            'object' => 'event',
            'type' => 'payment_intent.succeeded',
            'data' => [
                'object' => $paymentIntent,
            ],
        ]);

        $signature = WebhookSignature::generateSignatureHeader(
            $payload,
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
                'HTTP_STRIPE_SIGNATURE' => $signature,
            ],
            $payload
        );

        $response->assertStatus(422);

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => 'pending',
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'pending',
        ]);
    }

    public function test_stripe_webhook_does_not_complete_payment_when_succeeded_event_has_missing_payment_intent_id(): void
    {
        Config::set(
            'services.stripe.webhook_secret',
            'whsec_test_secret'
        );

        $order = Order::factory()->create([
            'status' => 'pending',
        ]);

        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'provider' => 'stripe',
            'stripe_payment_intent_id' => 'pi_missing_id',
            'currency' => 'idr',
            'gross_amount' => 100000,
            'status' => 'pending',
        ]);

        $paymentIntent = [
            'object' => 'payment_intent',
            'status' => 'succeeded',
            'currency' => 'idr',
            'amount' => 10000000,
            // id sengaja tidak diberikan
        ];

        $payload = json_encode([
            'id' => 'evt_missing_payment_intent_id',
            'object' => 'event',
            'type' => 'payment_intent.succeeded',
            'data' => [
                'object' => $paymentIntent,
            ],
        ]);

        $signature = WebhookSignature::generateSignatureHeader(
            $payload,
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
                'HTTP_STRIPE_SIGNATURE' => $signature,
            ],
            $payload
        );

        $response->assertStatus(422);

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => 'pending',
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'pending',
        ]);
    }

    public function test_stripe_webhook_does_not_fail_payment_when_failed_event_has_missing_payment_intent_id(): void
    {
        Config::set(
            'services.stripe.webhook_secret',
            'whsec_test_secret'
        );

        $order = Order::factory()->create([
            'status' => 'pending',
        ]);

        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'provider' => 'stripe',
            'stripe_payment_intent_id' => 'pi_failed_missing_id',
            'currency' => 'idr',
            'gross_amount' => 100000,
            'status' => 'pending',
        ]);

        $paymentIntent = [
            'object' => 'payment_intent',
            'status' => 'requires_payment_method',
            // id sengaja tidak diberikan
        ];

        $payload = json_encode([
            'id' => 'evt_failed_missing_payment_intent_id',
            'object' => 'event',
            'type' => 'payment_intent.payment_failed',
            'data' => [
                'object' => $paymentIntent,
            ],
        ]);

        $signature = WebhookSignature::generateSignatureHeader(
            $payload,
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
                'HTTP_STRIPE_SIGNATURE' => $signature,
            ],
            $payload
        );

        $response->assertStatus(422);

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => 'pending',
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'pending',
        ]);
    }
    
    public function test_stripe_webhook_does_not_fail_payment_when_failed_event_has_invalid_payment_intent_status(): void
    {
        Config::set(
            'services.stripe.webhook_secret',
            'whsec_test_secret'
        );

        $order = Order::factory()->create([
            'status' => 'pending',
        ]);

        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'provider' => 'stripe',
            'stripe_payment_intent_id' => 'pi_invalid_failed_status',
            'currency' => 'idr',
            'gross_amount' => 100000,
            'status' => 'pending',
        ]);

        $paymentIntent = [
            'id' => 'pi_invalid_failed_status',
            'object' => 'payment_intent',
            'status' => 'succeeded',
            'last_payment_error' => null,
        ];

        $payload = json_encode([
            'id' => 'evt_invalid_failed_status',
            'object' => 'event',
            'type' => 'payment_intent.payment_failed',
            'data' => [
                'object' => $paymentIntent,
            ],
        ]);

        $signature = WebhookSignature::generateSignatureHeader(
            $payload,
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
                'HTTP_STRIPE_SIGNATURE' => $signature,
            ],
            $payload
        );

        $response->assertStatus(422);

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => 'pending',
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'pending',
        ]);
    }

    public function test_stripe_webhook_does_not_cancel_payment_when_canceled_event_has_missing_payment_intent_id(): void
    {
        Config::set(
            'services.stripe.webhook_secret',
            'whsec_test_secret'
        );

        $order = Order::factory()->create([
            'status' => 'pending',
        ]);

        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'provider' => 'stripe',
            'stripe_payment_intent_id' => 'pi_canceled_missing_id',
            'currency' => 'idr',
            'gross_amount' => 100000,
            'status' => 'pending',
        ]);

        $paymentIntent = [
            'object' => 'payment_intent',
            'status' => 'canceled',
            // id sengaja tidak diberikan
        ];

        $payload = json_encode([
            'id' => 'evt_canceled_missing_payment_intent_id',
            'object' => 'event',
            'type' => 'payment_intent.canceled',
            'data' => [
                'object' => $paymentIntent,
            ],
        ]);

        $signature = WebhookSignature::generateSignatureHeader(
            $payload,
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
                'HTTP_STRIPE_SIGNATURE' => $signature,
            ],
            $payload
        );

        $response->assertStatus(422);

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => 'pending',
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'pending',
        ]);
    }

    public function test_stripe_webhook_does_not_cancel_payment_when_canceled_event_has_invalid_payment_intent_status(): void
    {
        Config::set(
            'services.stripe.webhook_secret',
            'whsec_test_secret'
        );

        $order = Order::factory()->create([
            'status' => 'pending',
        ]);

        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'provider' => 'stripe',
            'stripe_payment_intent_id' => 'pi_invalid_canceled_status',
            'currency' => 'idr',
            'gross_amount' => 100000,
            'status' => 'pending',
        ]);

        $paymentIntent = [
            'id' => 'pi_invalid_canceled_status',
            'object' => 'payment_intent',
            'status' => 'succeeded',
        ];

        $payload = json_encode([
            'id' => 'evt_invalid_canceled_status',
            'object' => 'event',
            'type' => 'payment_intent.canceled',
            'data' => [
                'object' => $paymentIntent,
            ],
        ]);

        $signature = WebhookSignature::generateSignatureHeader(
            $payload,
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
                'HTTP_STRIPE_SIGNATURE' => $signature,
            ],
            $payload
        );

        $response->assertStatus(422);

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => 'pending',
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'pending',
        ]);
    }

    public function test_stripe_webhook_does_not_fail_with_server_error_when_failed_event_has_missing_last_payment_error(): void
    {
        Config::set(
            'services.stripe.webhook_secret',
            'whsec_test_secret'
        );

        $order = Order::factory()->create([
            'status' => 'pending',
        ]);

        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'provider' => 'stripe',
            'stripe_payment_intent_id' => 'pi_missing_last_error',
            'currency' => 'idr',
            'gross_amount' => 100000,
            'status' => 'pending',
        ]);

        $paymentIntent = [
            'id' => 'pi_missing_last_error',
            'object' => 'payment_intent',
            'status' => 'requires_payment_method',
            // last_payment_error sengaja tidak diberikan
        ];

        $payload = json_encode([
            'id' => 'evt_missing_last_error',
            'object' => 'event',
            'type' => 'payment_intent.payment_failed',
            'data' => [
                'object' => $paymentIntent,
            ],
        ]);

        $signature = WebhookSignature::generateSignatureHeader(
            $payload,
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
                'HTTP_STRIPE_SIGNATURE' => $signature,
            ],
            $payload
        );

        $response->assertSuccessful();

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => 'failed',
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'pending',
        ]);
    }

    public function test_stripe_webhook_does_not_cancel_payment_when_canceled_event_has_missing_payment_intent_status(): void
    {
        Config::set(
            'services.stripe.webhook_secret',
            'whsec_test_secret'
        );

        $order = Order::factory()->create([
            'status' => 'pending',
        ]);

        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'provider' => 'stripe',
            'stripe_payment_intent_id' => 'pi_missing_canceled_status',
            'currency' => 'idr',
            'gross_amount' => 100000,
            'status' => 'pending',
        ]);

        $paymentIntent = [
            'id' => 'pi_missing_canceled_status',
            'object' => 'payment_intent',
            // status sengaja tidak diberikan
        ];

        $payload = json_encode([
            'id' => 'evt_missing_canceled_status',
            'object' => 'event',
            'type' => 'payment_intent.canceled',
            'data' => [
                'object' => $paymentIntent,
            ],
        ]);

        $signature = WebhookSignature::generateSignatureHeader(
            $payload,
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
                'HTTP_STRIPE_SIGNATURE' => $signature,
            ],
            $payload
        );

        $response->assertStatus(422);

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => 'pending',
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'pending',
        ]);
    }

    public function test_stripe_webhook_does_not_fail_payment_when_failed_event_has_missing_payment_intent_status(): void
    {
        Config::set(
            'services.stripe.webhook_secret',
            'whsec_test_secret'
        );

        $order = Order::factory()->create([
            'status' => 'pending',
        ]);

        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'provider' => 'stripe',
            'stripe_payment_intent_id' => 'pi_missing_failed_status',
            'currency' => 'idr',
            'gross_amount' => 100000,
            'status' => 'pending',
        ]);

        $paymentIntent = [
            'id' => 'pi_missing_failed_status',
            'object' => 'payment_intent',
            'last_payment_error' => null,
            // status sengaja tidak diberikan
        ];

        $payload = json_encode([
            'id' => 'evt_missing_failed_status',
            'object' => 'event',
            'type' => 'payment_intent.payment_failed',
            'data' => [
                'object' => $paymentIntent,
            ],
        ]);

        $signature = WebhookSignature::generateSignatureHeader(
            $payload,
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
                'HTTP_STRIPE_SIGNATURE' => $signature,
            ],
            $payload
        );

        $response->assertStatus(422);

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => 'pending',
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'pending',
        ]);
    }

    public function test_stripe_webhook_does_not_fail_payment_when_failed_event_has_canceled_payment_intent_status(): void
    {
        $user = User::factory()->create();

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'processing',
        ]);

        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'provider' => 'stripe',
            'stripe_payment_intent_id' => 'pi_failed_canceled_status',
            'currency' => 'IDR',
            'status' => 'pending',
            'transaction_status' => 'pending',
            'gross_amount' => 100000,
        ]);

        $payload = json_encode([
            'id' => 'evt_failed_canceled_status',
            'object' => 'event',
            'type' => 'payment_intent.payment_failed',
            'data' => [
                'object' => [
                    'id' => 'pi_failed_canceled_status',
                    'object' => 'payment_intent',
                    'status' => 'canceled',
                    'currency' => 'idr',
                    'amount' => 10000000,
                    'last_payment_error' => [
                        'code' => 'payment_canceled',
                        'message' => 'PaymentIntent was canceled.',
                    ],
                ],
            ],
        ]);

        $secret = 'whsec_test_secret';

        Config::set(
            'services.stripe.webhook_secret',
            $secret
        );

        $signature = WebhookSignature::generateSignatureHeader(
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

        $response->assertStatus(422);

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => 'pending',
            'transaction_status' => 'pending',
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'processing',
        ]);
    }

    public function test_stripe_webhook_does_not_fail_payment_when_failed_event_has_processing_payment_intent_status(): void
    {
        $user = User::factory()->create();

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'processing',
        ]);

        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'provider' => 'stripe',
            'stripe_payment_intent_id' => 'pi_failed_processing_status',
            'currency' => 'IDR',
            'status' => 'pending',
            'transaction_status' => 'pending',
            'gross_amount' => 100000,
        ]);

        $payload = json_encode([
            'id' => 'evt_failed_processing_status',
            'object' => 'event',
            'type' => 'payment_intent.payment_failed',
            'data' => [
                'object' => [
                    'id' => 'pi_failed_processing_status',
                    'object' => 'payment_intent',
                    'status' => 'processing',
                    'currency' => 'idr',
                    'amount' => 10000000,
                    'last_payment_error' => [
                        'code' => 'payment_failed',
                        'message' => 'Payment is still processing.',
                    ],
                ],
            ],
        ]);

        $secret = 'whsec_test_secret';

        Config::set(
            'services.stripe.webhook_secret',
            $secret
        );

        $signature = WebhookSignature::generateSignatureHeader(
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

        $response->assertStatus(422);

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => 'pending',
            'transaction_status' => 'pending',
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'processing',
        ]);
    }

    public function test_stripe_webhook_does_not_cancel_payment_when_canceled_event_has_requires_payment_method_status(): void
    {
        $user = User::factory()->create();

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'processing',
        ]);

        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'provider' => 'stripe',
            'stripe_payment_intent_id' => 'pi_canceled_requires_payment_method',
            'currency' => 'IDR',
            'status' => 'pending',
            'transaction_status' => 'pending',
            'gross_amount' => 100000,
        ]);

        $payload = json_encode([
            'id' => 'evt_canceled_requires_payment_method',
            'object' => 'event',
            'type' => 'payment_intent.canceled',
            'data' => [
                'object' => [
                    'id' => 'pi_canceled_requires_payment_method',
                    'object' => 'payment_intent',
                    'status' => 'requires_payment_method',
                    'currency' => 'idr',
                    'amount' => 10000000,
                ],
            ],
        ]);

        $secret = 'whsec_test_secret';

        Config::set(
            'services.stripe.webhook_secret',
            $secret
        );

        $signature = WebhookSignature::generateSignatureHeader(
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

        $response->assertStatus(422);

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => 'pending',
            'transaction_status' => 'pending',
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'processing',
        ]);
    }

    public function test_stripe_webhook_does_not_cancel_payment_when_canceled_event_has_processing_payment_intent_status(): void
    {
        $user = User::factory()->create();

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'processing',
        ]);

        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'provider' => 'stripe',
            'stripe_payment_intent_id' => 'pi_canceled_processing_status',
            'currency' => 'IDR',
            'status' => 'pending',
            'transaction_status' => 'pending',
            'gross_amount' => 100000,
        ]);

        $payload = json_encode([
            'id' => 'evt_canceled_processing_status',
            'object' => 'event',
            'type' => 'payment_intent.canceled',
            'data' => [
                'object' => [
                    'id' => 'pi_canceled_processing_status',
                    'object' => 'payment_intent',
                    'status' => 'processing',
                    'currency' => 'idr',
                    'amount' => 10000000,
                ],
            ],
        ]);

        $secret = 'whsec_test_secret';

        Config::set(
            'services.stripe.webhook_secret',
            $secret
        );

        $signature = WebhookSignature::generateSignatureHeader(
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

        $response->assertStatus(422);

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => 'pending',
            'transaction_status' => 'pending',
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'processing',
        ]);
    }

    public function test_stripe_webhook_does_not_fail_payment_when_failed_event_has_requires_action_payment_intent_status(): void
    {
        $user = User::factory()->create();

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'processing',
        ]);

        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'provider' => 'stripe',
            'stripe_payment_intent_id' => 'pi_failed_requires_action_status',
            'currency' => 'IDR',
            'status' => 'pending',
            'transaction_status' => 'pending',
            'gross_amount' => 100000,
        ]);

        $payload = json_encode([
            'id' => 'evt_failed_requires_action_status',
            'object' => 'event',
            'type' => 'payment_intent.payment_failed',
            'data' => [
                'object' => [
                    'id' => 'pi_failed_requires_action_status',
                    'object' => 'payment_intent',
                    'status' => 'requires_action',
                    'currency' => 'idr',
                    'amount' => 10000000,
                    'last_payment_error' => [
                        'code' => 'authentication_required',
                        'message' => 'Additional authentication is required.',
                    ],
                ],
            ],
        ]);

        $secret = 'whsec_test_secret';

        Config::set(
            'services.stripe.webhook_secret',
            $secret
        );

        $signature = WebhookSignature::generateSignatureHeader(
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

        $response->assertStatus(422);

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => 'pending',
            'transaction_status' => 'pending',
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'processing',
        ]);
    }

    public function test_stripe_webhook_does_not_fail_payment_when_failed_event_has_requires_capture_payment_intent_status(): void
    {
        $user = User::factory()->create();

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'processing',
        ]);

        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'provider' => 'stripe',
            'stripe_payment_intent_id' => 'pi_failed_requires_capture_status',
            'currency' => 'IDR',
            'status' => 'pending',
            'transaction_status' => 'pending',
            'gross_amount' => 100000,
        ]);

        $payload = json_encode([
            'id' => 'evt_failed_requires_capture_status',
            'object' => 'event',
            'type' => 'payment_intent.payment_failed',
            'data' => [
                'object' => [
                    'id' => 'pi_failed_requires_capture_status',
                    'object' => 'payment_intent',
                    'status' => 'requires_capture',
                    'currency' => 'idr',
                    'amount' => 10000000,
                    'last_payment_error' => [
                        'code' => 'payment_not_captured',
                        'message' => 'Payment has not been captured.',
                    ],
                ],
            ],
        ]);

        $secret = 'whsec_test_secret';

        Config::set(
            'services.stripe.webhook_secret',
            $secret
        );

        $signature = WebhookSignature::generateSignatureHeader(
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

        $response->assertStatus(422);

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => 'pending',
            'transaction_status' => 'pending',
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'processing',
        ]);
    }

    public function test_stripe_webhook_does_not_cancel_payment_when_canceled_event_has_requires_action_payment_intent_status(): void
    {
        $user = User::factory()->create();

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'processing',
        ]);

        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'provider' => 'stripe',
            'stripe_payment_intent_id' => 'pi_canceled_requires_action_status',
            'currency' => 'IDR',
            'status' => 'pending',
            'transaction_status' => 'pending',
            'gross_amount' => 100000,
        ]);

        $payload = json_encode([
            'id' => 'evt_canceled_requires_action_status',
            'object' => 'event',
            'type' => 'payment_intent.canceled',
            'data' => [
                'object' => [
                    'id' => 'pi_canceled_requires_action_status',
                    'object' => 'payment_intent',
                    'status' => 'requires_action',
                    'currency' => 'idr',
                    'amount' => 10000000,
                ],
            ],
        ]);

        $secret = 'whsec_test_secret';

        Config::set(
            'services.stripe.webhook_secret',
            $secret
        );

        $signature = WebhookSignature::generateSignatureHeader(
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

        $response->assertStatus(422);

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => 'pending',
            'transaction_status' => 'pending',
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'processing',
        ]);
    }

    public function test_stripe_webhook_does_not_cancel_payment_when_canceled_event_has_requires_capture_payment_intent_status(): void
    {
        $user = User::factory()->create();

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'processing',
        ]);

        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'provider' => 'stripe',
            'stripe_payment_intent_id' => 'pi_canceled_requires_capture_status',
            'currency' => 'IDR',
            'status' => 'pending',
            'transaction_status' => 'pending',
            'gross_amount' => 100000,
        ]);

        $payload = json_encode([
            'id' => 'evt_canceled_requires_capture_status',
            'object' => 'event',
            'type' => 'payment_intent.canceled',
            'data' => [
                'object' => [
                    'id' => 'pi_canceled_requires_capture_status',
                    'object' => 'payment_intent',
                    'status' => 'requires_capture',
                    'currency' => 'idr',
                    'amount' => 10000000,
                ],
            ],
        ]);

        $secret = 'whsec_test_secret';

        Config::set(
            'services.stripe.webhook_secret',
            $secret
        );

        $signature = WebhookSignature::generateSignatureHeader(
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

        $response->assertStatus(422);

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => 'pending',
            'transaction_status' => 'pending',
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'processing',
        ]);
    }

    public function test_stripe_webhook_does_not_complete_payment_when_succeeded_event_has_zero_amount(): void
    {
        $user = User::factory()->create();

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'processing',
        ]);

        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'provider' => 'stripe',
            'stripe_payment_intent_id' => 'pi_succeeded_zero_amount',
            'currency' => 'IDR',
            'status' => 'pending',
            'transaction_status' => 'pending',
            'gross_amount' => 100000,
        ]);

        $payload = json_encode([
            'id' => 'evt_succeeded_zero_amount',
            'object' => 'event',
            'type' => 'payment_intent.succeeded',
            'data' => [
                'object' => [
                    'id' => 'pi_succeeded_zero_amount',
                    'object' => 'payment_intent',
                    'status' => 'succeeded',
                    'currency' => 'idr',
                    'amount' => 0,
                ],
            ],
        ]);

        $secret = 'whsec_test_secret';

        Config::set(
            'services.stripe.webhook_secret',
            $secret
        );

        $signature = WebhookSignature::generateSignatureHeader(
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

        $response->assertStatus(422);

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => 'pending',
            'transaction_status' => 'pending',
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'processing',
        ]);
    }

    public function test_stripe_webhook_does_not_complete_payment_when_succeeded_event_has_negative_amount(): void
    {
        $user = User::factory()->create();

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'processing',
        ]);

        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'provider' => 'stripe',
            'stripe_payment_intent_id' => 'pi_succeeded_negative_amount',
            'currency' => 'IDR',
            'status' => 'pending',
            'transaction_status' => 'pending',
            'gross_amount' => 100000,
        ]);

        $payload = json_encode([
            'id' => 'evt_succeeded_negative_amount',
            'object' => 'event',
            'type' => 'payment_intent.succeeded',
            'data' => [
                'object' => [
                    'id' => 'pi_succeeded_negative_amount',
                    'object' => 'payment_intent',
                    'status' => 'succeeded',
                    'currency' => 'idr',
                    'amount' => -10000000,
                ],
            ],
        ]);

        $secret = 'whsec_test_secret';

        Config::set(
            'services.stripe.webhook_secret',
            $secret
        );

        $signature = WebhookSignature::generateSignatureHeader(
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

        $response->assertStatus(422);

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => 'pending',
            'transaction_status' => 'pending',
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'processing',
        ]);
    }

    public function test_stripe_webhook_does_not_complete_payment_when_succeeded_event_has_string_amount(): void
    {
        $user = User::factory()->create();

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'processing',
        ]);

        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'provider' => 'stripe',
            'stripe_payment_intent_id' => 'pi_succeeded_string_amount',
            'currency' => 'IDR',
            'status' => 'pending',
            'transaction_status' => 'pending',
            'gross_amount' => 100000,
        ]);

        $payload = json_encode([
            'id' => 'evt_succeeded_string_amount',
            'object' => 'event',
            'type' => 'payment_intent.succeeded',
            'data' => [
                'object' => [
                    'id' => 'pi_succeeded_string_amount',
                    'object' => 'payment_intent',
                    'status' => 'succeeded',
                    'currency' => 'idr',
                    'amount' => '10000000',
                ],
            ],
        ]);

        $secret = 'whsec_test_secret';

        Config::set(
            'services.stripe.webhook_secret',
            $secret
        );

        $signature = WebhookSignature::generateSignatureHeader(
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

        $response->assertStatus(422);

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => 'pending',
            'transaction_status' => 'pending',
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'processing',
        ]);
    }

    public function test_stripe_webhook_does_not_complete_payment_when_succeeded_event_has_invalid_currency_type(): void
    {
        $user = User::factory()->create();

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'processing',
        ]);

        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'provider' => 'stripe',
            'stripe_payment_intent_id' => 'pi_succeeded_invalid_currency_type',
            'currency' => 'IDR',
            'status' => 'pending',
            'transaction_status' => 'pending',
            'gross_amount' => 100000,
        ]);

        $payload = json_encode([
            'id' => 'evt_succeeded_invalid_currency_type',
            'object' => 'event',
            'type' => 'payment_intent.succeeded',
            'data' => [
                'object' => [
                    'id' => 'pi_succeeded_invalid_currency_type',
                    'object' => 'payment_intent',
                    'status' => 'succeeded',
                    'currency' => 123,
                    'amount' => 10000000,
                ],
            ],
        ]);

        $secret = 'whsec_test_secret';

        Config::set(
            'services.stripe.webhook_secret',
            $secret
        );

        $signature = WebhookSignature::generateSignatureHeader(
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

        $response->assertStatus(422);

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => 'pending',
            'transaction_status' => 'pending',
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'processing',
        ]);
    }

    public function test_stripe_webhook_does_not_complete_payment_when_succeeded_event_has_empty_currency(): void
    {
        $user = User::factory()->create();

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'processing',
        ]);

        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'provider' => 'stripe',
            'stripe_payment_intent_id' => 'pi_succeeded_empty_currency',
            'currency' => 'IDR',
            'status' => 'pending',
            'transaction_status' => 'pending',
            'gross_amount' => 100000,
        ]);

        $payload = json_encode([
            'id' => 'evt_succeeded_empty_currency',
            'object' => 'event',
            'type' => 'payment_intent.succeeded',
            'data' => [
                'object' => [
                    'id' => 'pi_succeeded_empty_currency',
                    'object' => 'payment_intent',
                    'status' => 'succeeded',
                    'currency' => '',
                    'amount' => 10000000,
                ],
            ],
        ]);

        $secret = 'whsec_test_secret';

        Config::set(
            'services.stripe.webhook_secret',
            $secret
        );

        $signature = WebhookSignature::generateSignatureHeader(
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

        $response->assertStatus(422);

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => 'pending',
            'transaction_status' => 'pending',
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'processing',
        ]);
    }

    public function test_stripe_webhook_does_not_complete_payment_when_succeeded_event_has_whitespace_currency(): void
    {
        $user = User::factory()->create();

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'processing',
        ]);

        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'provider' => 'stripe',
            'stripe_payment_intent_id' => 'pi_succeeded_whitespace_currency',
            'currency' => 'IDR',
            'status' => 'pending',
            'transaction_status' => 'pending',
            'gross_amount' => 100000,
        ]);

        $payload = json_encode([
            'id' => 'evt_succeeded_whitespace_currency',
            'object' => 'event',
            'type' => 'payment_intent.succeeded',
            'data' => [
                'object' => [
                    'id' => 'pi_succeeded_whitespace_currency',
                    'object' => 'payment_intent',
                    'status' => 'succeeded',
                    'currency' => '   ',
                    'amount' => 10000000,
                ],
            ],
        ]);

        $secret = 'whsec_test_secret';

        Config::set(
            'services.stripe.webhook_secret',
            $secret
        );

        $signature = WebhookSignature::generateSignatureHeader(
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

        $response->assertStatus(422);

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => 'pending',
            'transaction_status' => 'pending',
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'processing',
        ]);
    }

    public function test_stripe_webhook_does_not_fail_payment_when_failed_event_has_invalid_status_type(): void
    {
        $user = User::factory()->create();

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'processing',
        ]);

        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'provider' => 'stripe',
            'stripe_payment_intent_id' => 'pi_failed_invalid_status_type',
            'currency' => 'IDR',
            'status' => 'pending',
            'transaction_status' => 'pending',
            'gross_amount' => 100000,
        ]);

        $payload = json_encode([
            'id' => 'evt_failed_invalid_status_type',
            'object' => 'event',
            'type' => 'payment_intent.payment_failed',
            'data' => [
                'object' => [
                    'id' => 'pi_failed_invalid_status_type',
                    'object' => 'payment_intent',
                    'status' => 123,
                    'currency' => 'idr',
                    'amount' => 10000000,
                    'last_payment_error' => [
                        'code' => 'payment_failed',
                        'message' => 'Invalid status type.',
                    ],
                ],
            ],
        ]);

        $secret = 'whsec_test_secret';

        Config::set(
            'services.stripe.webhook_secret',
            $secret
        );

        $signature = WebhookSignature::generateSignatureHeader(
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

        $response->assertStatus(422);

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => 'pending',
            'transaction_status' => 'pending',
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'processing',
        ]);
    }

    public function test_stripe_webhook_does_not_cancel_payment_when_canceled_event_has_invalid_status_type(): void
    {
        $user = User::factory()->create();

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'processing',
        ]);

        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'provider' => 'stripe',
            'stripe_payment_intent_id' => 'pi_canceled_invalid_status_type',
            'currency' => 'IDR',
            'status' => 'pending',
            'transaction_status' => 'pending',
            'gross_amount' => 100000,
        ]);

        $payload = json_encode([
            'id' => 'evt_canceled_invalid_status_type',
            'object' => 'event',
            'type' => 'payment_intent.canceled',
            'data' => [
                'object' => [
                    'id' => 'pi_canceled_invalid_status_type',
                    'object' => 'payment_intent',
                    'status' => 123,
                    'currency' => 'idr',
                    'amount' => 10000000,
                ],
            ],
        ]);

        $secret = 'whsec_test_secret';

        Config::set(
            'services.stripe.webhook_secret',
            $secret
        );

        $signature = WebhookSignature::generateSignatureHeader(
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

        $response->assertStatus(422);

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => 'pending',
            'transaction_status' => 'pending',
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'processing',
        ]);
    }
    
    public function test_stripe_webhook_does_not_complete_payment_when_succeeded_event_has_float_amount(): void
    {
        $user = User::factory()->create();

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'processing',
        ]);

        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'provider' => 'stripe',
            'stripe_payment_intent_id' => 'pi_succeeded_float_amount',
            'currency' => 'IDR',
            'status' => 'pending',
            'transaction_status' => 'pending',
            'gross_amount' => 100000,
        ]);

        $payload = json_encode([
            'id' => 'evt_succeeded_float_amount',
            'object' => 'event',
            'type' => 'payment_intent.succeeded',
            'data' => [
                'object' => [
                    'id' => 'pi_succeeded_float_amount',
                    'object' => 'payment_intent',
                    'status' => 'succeeded',
                    'currency' => 'idr',
                    'amount' => 10000000.5,
                ],
            ],
        ]);

        $secret = 'whsec_test_secret';

        Config::set(
            'services.stripe.webhook_secret',
            $secret
        );

        $signature = WebhookSignature::generateSignatureHeader(
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

        $response->assertStatus(422);

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => 'pending',
            'transaction_status' => 'pending',
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'processing',
        ]);
    }

    public function test_stripe_webhook_does_not_complete_payment_when_succeeded_event_has_invalid_status_type(): void
    {
        $user = User::factory()->create();

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'processing',
        ]);

        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'provider' => 'stripe',
            'stripe_payment_intent_id' => 'pi_succeeded_invalid_status_type',
            'currency' => 'IDR',
            'status' => 'pending',
            'transaction_status' => 'pending',
            'gross_amount' => 100000,
        ]);

        $payload = json_encode([
            'id' => 'evt_succeeded_invalid_status_type',
            'object' => 'event',
            'type' => 'payment_intent.succeeded',
            'data' => [
                'object' => [
                    'id' => 'pi_succeeded_invalid_status_type',
                    'object' => 'payment_intent',
                    'status' => 123,
                    'currency' => 'idr',
                    'amount' => 10000000,
                ],
            ],
        ]);

        $secret = 'whsec_test_secret';

        Config::set(
            'services.stripe.webhook_secret',
            $secret
        );

        $signature = WebhookSignature::generateSignatureHeader(
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

        $response->assertStatus(422);

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => 'pending',
            'transaction_status' => 'pending',
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'processing',
        ]);
    }

    public function test_stripe_webhook_does_not_complete_payment_when_succeeded_event_has_missing_payment_intent_status(): void
    {
        $user = User::factory()->create();

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'processing',
        ]);

        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'provider' => 'stripe',
            'stripe_payment_intent_id' => 'pi_succeeded_missing_status',
            'currency' => 'IDR',
            'status' => 'pending',
            'transaction_status' => 'pending',
            'gross_amount' => 100000,
        ]);

        $payload = json_encode([
            'id' => 'evt_succeeded_missing_status',
            'object' => 'event',
            'type' => 'payment_intent.succeeded',
            'data' => [
                'object' => [
                    'id' => 'pi_succeeded_missing_status',
                    'object' => 'payment_intent',
                    'currency' => 'idr',
                    'amount' => 10000000,
                ],
            ],
        ]);

        $secret = 'whsec_test_secret';

        Config::set(
            'services.stripe.webhook_secret',
            $secret
        );

        $signature = WebhookSignature::generateSignatureHeader(
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

        $response->assertStatus(422);

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => 'pending',
            'transaction_status' => 'pending',
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'processing',
        ]);
    }

    public function test_stripe_webhook_does_not_complete_payment_when_succeeded_event_has_malformed_currency(): void
    {
        $user = User::factory()->create();

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'processing',
        ]);

        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'provider' => 'stripe',
            'stripe_payment_intent_id' => 'pi_succeeded_malformed_currency',
            'currency' => 'IDR',
            'status' => 'pending',
            'transaction_status' => 'pending',
            'gross_amount' => 100000,
        ]);

        $payload = json_encode([
            'id' => 'evt_succeeded_malformed_currency',
            'object' => 'event',
            'type' => 'payment_intent.succeeded',
            'data' => [
                'object' => [
                    'id' => 'pi_succeeded_malformed_currency',
                    'object' => 'payment_intent',
                    'status' => 'succeeded',
                    'currency' => '123',
                    'amount' => 10000000,
                ],
            ],
        ]);

        $secret = 'whsec_test_secret';

        Config::set(
            'services.stripe.webhook_secret',
            $secret
        );

        $signature = WebhookSignature::generateSignatureHeader(
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

        $response->assertStatus(422);

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => 'pending',
            'transaction_status' => 'pending',
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'processing',
        ]);
    }

    public function test_stripe_webhook_does_not_complete_payment_when_succeeded_event_has_boolean_currency(): void
    {
        $user = User::factory()->create();

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'processing',
        ]);

        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'provider' => 'stripe',
            'stripe_payment_intent_id' => 'pi_succeeded_boolean_currency',
            'currency' => 'IDR',
            'status' => 'pending',
            'transaction_status' => 'pending',
            'gross_amount' => 100000,
        ]);

        $payload = json_encode([
            'id' => 'evt_succeeded_boolean_currency',
            'object' => 'event',
            'type' => 'payment_intent.succeeded',
            'data' => [
                'object' => [
                    'id' => 'pi_succeeded_boolean_currency',
                    'object' => 'payment_intent',
                    'status' => 'succeeded',
                    'currency' => true,
                    'amount' => 10000000,
                ],
            ],
        ]);

        $secret = 'whsec_test_secret';

        Config::set(
            'services.stripe.webhook_secret',
            $secret
        );

        $signature = WebhookSignature::generateSignatureHeader(
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

        $response->assertStatus(422);

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => 'pending',
            'transaction_status' => 'pending',
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'processing',
        ]);
    }

    public function test_stripe_webhook_does_not_complete_payment_when_succeeded_event_has_array_currency(): void
    {
        $user = User::factory()->create();

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'processing',
        ]);

        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'provider' => 'stripe',
            'stripe_payment_intent_id' => 'pi_succeeded_array_currency',
            'currency' => 'IDR',
            'status' => 'pending',
            'transaction_status' => 'pending',
            'gross_amount' => 100000,
        ]);

        $payload = json_encode([
            'id' => 'evt_succeeded_array_currency',
            'object' => 'event',
            'type' => 'payment_intent.succeeded',
            'data' => [
                'object' => [
                    'id' => 'pi_succeeded_array_currency',
                    'object' => 'payment_intent',
                    'status' => 'succeeded',
                    'currency' => ['idr'],
                    'amount' => 10000000,
                ],
            ],
        ]);

        $secret = 'whsec_test_secret';

        Config::set(
            'services.stripe.webhook_secret',
            $secret
        );

        $signature = WebhookSignature::generateSignatureHeader(
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

        $response->assertStatus(422);

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => 'pending',
            'transaction_status' => 'pending',
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'processing',
        ]);
    }

    public function test_stripe_webhook_does_not_complete_payment_when_succeeded_event_has_object_currency(): void
    {
        $user = User::factory()->create();

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'processing',
        ]);

        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'provider' => 'stripe',
            'stripe_payment_intent_id' => 'pi_succeeded_object_currency',
            'currency' => 'IDR',
            'status' => 'pending',
            'transaction_status' => 'pending',
            'gross_amount' => 100000,
        ]);

        $payload = json_encode([
            'id' => 'evt_succeeded_object_currency',
            'object' => 'event',
            'type' => 'payment_intent.succeeded',
            'data' => [
                'object' => [
                    'id' => 'pi_succeeded_object_currency',
                    'object' => 'payment_intent',
                    'status' => 'succeeded',
                    'currency' => [
                        'code' => 'idr',
                    ],
                    'amount' => 10000000,
                ],
            ],
        ]);

        $secret = 'whsec_test_secret';

        Config::set(
            'services.stripe.webhook_secret',
            $secret
        );

        $signature = WebhookSignature::generateSignatureHeader(
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

        $response->assertStatus(422);

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => 'pending',
            'transaction_status' => 'pending',
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'processing',
        ]);
    }

    public function test_stripe_webhook_does_not_complete_payment_when_succeeded_event_has_boolean_amount(): void
    {
        $user = User::factory()->create();

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'processing',
        ]);

        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'provider' => 'stripe',
            'stripe_payment_intent_id' => 'pi_succeeded_boolean_amount',
            'currency' => 'IDR',
            'status' => 'pending',
            'transaction_status' => 'pending',
            'gross_amount' => 100000,
        ]);

        $payload = json_encode([
            'id' => 'evt_succeeded_boolean_amount',
            'object' => 'event',
            'type' => 'payment_intent.succeeded',
            'data' => [
                'object' => [
                    'id' => 'pi_succeeded_boolean_amount',
                    'object' => 'payment_intent',
                    'status' => 'succeeded',
                    'currency' => 'idr',
                    'amount' => true,
                ],
            ],
        ]);

        $secret = 'whsec_test_secret';

        Config::set(
            'services.stripe.webhook_secret',
            $secret
        );

        $signature = WebhookSignature::generateSignatureHeader(
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

        $response->assertStatus(422);

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => 'pending',
            'transaction_status' => 'pending',
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'processing',
        ]);
    }

    public function test_stripe_webhook_does_not_complete_payment_when_succeeded_event_has_array_amount(): void
    {
        $user = User::factory()->create();

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'processing',
        ]);

        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'provider' => 'stripe',
            'stripe_payment_intent_id' => 'pi_succeeded_array_amount',
            'currency' => 'IDR',
            'status' => 'pending',
            'transaction_status' => 'pending',
            'gross_amount' => 100000,
        ]);

        $payload = json_encode([
            'id' => 'evt_succeeded_array_amount',
            'object' => 'event',
            'type' => 'payment_intent.succeeded',
            'data' => [
                'object' => [
                    'id' => 'pi_succeeded_array_amount',
                    'object' => 'payment_intent',
                    'status' => 'succeeded',
                    'currency' => 'idr',
                    'amount' => [10000000],
                ],
            ],
        ]);

        $secret = 'whsec_test_secret';

        Config::set(
            'services.stripe.webhook_secret',
            $secret
        );

        $signature = WebhookSignature::generateSignatureHeader(
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

        $response->assertStatus(422);

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => 'pending',
            'transaction_status' => 'pending',
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'processing',
        ]);
    }

    public function test_stripe_webhook_does_not_complete_payment_when_succeeded_event_has_object_amount(): void
    {
        $user = User::factory()->create();

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'processing',
        ]);

        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'provider' => 'stripe',
            'stripe_payment_intent_id' => 'pi_succeeded_object_amount',
            'currency' => 'IDR',
            'status' => 'pending',
            'transaction_status' => 'pending',
            'gross_amount' => 100000,
        ]);

        $payload = json_encode([
            'id' => 'evt_succeeded_object_amount',
            'object' => 'event',
            'type' => 'payment_intent.succeeded',
            'data' => [
                'object' => [
                    'id' => 'pi_succeeded_object_amount',
                    'object' => 'payment_intent',
                    'status' => 'succeeded',
                    'currency' => 'idr',
                    'amount' => [
                        'value' => 10000000,
                    ],
                ],
            ],
        ]);

        $secret = 'whsec_test_secret';

        Config::set(
            'services.stripe.webhook_secret',
            $secret
        );

        $signature = WebhookSignature::generateSignatureHeader(
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

        $response->assertStatus(422);

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => 'pending',
            'transaction_status' => 'pending',
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'processing',
        ]);
    }

    public function test_stripe_webhook_does_not_complete_payment_when_succeeded_event_has_invalid_payment_intent_id_type(): void
    {
        $user = User::factory()->create();

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'processing',
        ]);

        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'provider' => 'stripe',
            'stripe_payment_intent_id' => 'pi_succeeded_invalid_id_type',
            'currency' => 'IDR',
            'status' => 'pending',
            'transaction_status' => 'pending',
            'gross_amount' => 100000,
        ]);

        $payload = json_encode([
            'id' => 'evt_succeeded_invalid_id_type',
            'object' => 'event',
            'type' => 'payment_intent.succeeded',
            'data' => [
                'object' => [
                    'id' => 123456,
                    'object' => 'payment_intent',
                    'status' => 'succeeded',
                    'currency' => 'idr',
                    'amount' => 10000000,
                ],
            ],
        ]);

        $secret = 'whsec_test_secret';

        Config::set(
            'services.stripe.webhook_secret',
            $secret
        );

        $signature = WebhookSignature::generateSignatureHeader(
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

        $response->assertStatus(422);

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => 'pending',
            'transaction_status' => 'pending',
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'processing',
        ]);
    }

    public function test_stripe_webhook_does_not_fail_payment_when_failed_event_has_invalid_payment_intent_id_type(): void
    {
        $user = User::factory()->create();

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'processing',
        ]);

        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'provider' => 'stripe',
            'stripe_payment_intent_id' => 'pi_failed_invalid_id_type',
            'currency' => 'IDR',
            'status' => 'pending',
            'transaction_status' => 'pending',
            'gross_amount' => 100000,
        ]);

        $payload = json_encode([
            'id' => 'evt_failed_invalid_id_type',
            'object' => 'event',
            'type' => 'payment_intent.payment_failed',
            'data' => [
                'object' => [
                    'id' => 123456,
                    'object' => 'payment_intent',
                    'status' => 'requires_payment_method',
                    'currency' => 'idr',
                    'amount' => 10000000,
                ],
            ],
        ]);

        $secret = 'whsec_test_secret';

        Config::set(
            'services.stripe.webhook_secret',
            $secret
        );

        $signature = WebhookSignature::generateSignatureHeader(
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

        $response->assertStatus(422);

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => 'pending',
            'transaction_status' => 'pending',
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'processing',
        ]);
    }

    public function test_stripe_webhook_does_not_cancel_payment_when_canceled_event_has_invalid_payment_intent_id_type(): void
    {
        $user = User::factory()->create();

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'processing',
        ]);

        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'provider' => 'stripe',
            'stripe_payment_intent_id' => 'pi_canceled_invalid_id_type',
            'currency' => 'IDR',
            'status' => 'pending',
            'transaction_status' => 'pending',
            'gross_amount' => 100000,
        ]);

        $payload = json_encode([
            'id' => 'evt_canceled_invalid_id_type',
            'object' => 'event',
            'type' => 'payment_intent.canceled',
            'data' => [
                'object' => [
                    'id' => 123456,
                    'object' => 'payment_intent',
                    'status' => 'canceled',
                    'currency' => 'idr',
                    'amount' => 10000000,
                ],
            ],
        ]);

        $secret = 'whsec_test_secret';

        Config::set(
            'services.stripe.webhook_secret',
            $secret
        );

        $signature = WebhookSignature::generateSignatureHeader(
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

        $response->assertStatus(422);

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => 'pending',
            'transaction_status' => 'pending',
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'processing',
        ]);
    }

    public function test_stripe_webhook_does_not_complete_payment_when_succeeded_event_has_array_payment_intent_id(): void
    {
        $user = User::factory()->create();

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'processing',
        ]);

        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'provider' => 'stripe',
            'stripe_payment_intent_id' => 'pi_succeeded_array_id',
            'currency' => 'IDR',
            'status' => 'pending',
            'transaction_status' => 'pending',
            'gross_amount' => 100000,
        ]);

        $payload = json_encode([
            'id' => 'evt_succeeded_array_id',
            'object' => 'event',
            'type' => 'payment_intent.succeeded',
            'data' => [
                'object' => [
                    'id' => ['invalid-id'],
                    'object' => 'payment_intent',
                    'status' => 'succeeded',
                    'currency' => 'idr',
                    'amount' => 10000000,
                ],
            ],
        ]);

        $secret = 'whsec_test_secret';

        Config::set(
            'services.stripe.webhook_secret',
            $secret
        );

        $signature = WebhookSignature::generateSignatureHeader(
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

        $response->assertStatus(422);

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => 'pending',
            'transaction_status' => 'pending',
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'processing',
        ]);
    }

    public function test_stripe_webhook_does_not_fail_payment_when_failed_event_has_array_payment_intent_id(): void
    {
        $user = User::factory()->create();

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'processing',
        ]);

        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'provider' => 'stripe',
            'stripe_payment_intent_id' => 'pi_failed_array_id',
            'currency' => 'IDR',
            'status' => 'pending',
            'transaction_status' => 'pending',
            'gross_amount' => 100000,
        ]);

        $payload = json_encode([
            'id' => 'evt_failed_array_id',
            'object' => 'event',
            'type' => 'payment_intent.payment_failed',
            'data' => [
                'object' => [
                    'id' => ['invalid-id'],
                    'object' => 'payment_intent',
                    'status' => 'requires_payment_method',
                    'currency' => 'idr',
                    'amount' => 10000000,
                ],
            ],
        ]);

        $secret = 'whsec_test_secret';

        Config::set(
            'services.stripe.webhook_secret',
            $secret
        );

        $signature = WebhookSignature::generateSignatureHeader(
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

        $response->assertStatus(422);

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => 'pending',
            'transaction_status' => 'pending',
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'processing',
        ]);
    }

    public function test_stripe_webhook_does_not_cancel_payment_when_canceled_event_has_array_payment_intent_id(): void
    {
        $user = User::factory()->create();

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'processing',
        ]);

        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'provider' => 'stripe',
            'stripe_payment_intent_id' => 'pi_canceled_array_id',
            'currency' => 'IDR',
            'status' => 'pending',
            'transaction_status' => 'pending',
            'gross_amount' => 100000,
        ]);

        $payload = json_encode([
            'id' => 'evt_canceled_array_id',
            'object' => 'event',
            'type' => 'payment_intent.canceled',
            'data' => [
                'object' => [
                    'id' => ['invalid-id'],
                    'object' => 'payment_intent',
                    'status' => 'canceled',
                    'currency' => 'idr',
                    'amount' => 10000000,
                ],
            ],
        ]);

        $secret = 'whsec_test_secret';

        Config::set(
            'services.stripe.webhook_secret',
            $secret
        );

        $signature = WebhookSignature::generateSignatureHeader(
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

        $response->assertStatus(422);

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => 'pending',
            'transaction_status' => 'pending',
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'processing',
        ]);
    }

    public function test_stripe_webhook_does_not_complete_payment_when_succeeded_event_has_boolean_payment_intent_id(): void
    {
        $user = User::factory()->create();

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'processing',
        ]);

        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'provider' => 'stripe',
            'stripe_payment_intent_id' => 'pi_succeeded_boolean_id',
            'currency' => 'IDR',
            'status' => 'pending',
            'transaction_status' => 'pending',
            'gross_amount' => 100000,
        ]);

        $payload = json_encode([
            'id' => 'evt_succeeded_boolean_id',
            'object' => 'event',
            'type' => 'payment_intent.succeeded',
            'data' => [
                'object' => [
                    'id' => true,
                    'object' => 'payment_intent',
                    'status' => 'succeeded',
                    'currency' => 'idr',
                    'amount' => 10000000,
                ],
            ],
        ]);

        $secret = 'whsec_test_secret';

        Config::set(
            'services.stripe.webhook_secret',
            $secret
        );

        $signature = WebhookSignature::generateSignatureHeader(
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

        $response->assertStatus(422);

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => 'pending',
            'transaction_status' => 'pending',
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'processing',
        ]);
    }

    public function test_stripe_webhook_does_not_fail_payment_when_failed_event_has_boolean_payment_intent_id(): void
    {
        $user = User::factory()->create();

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'processing',
        ]);

        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'provider' => 'stripe',
            'stripe_payment_intent_id' => 'pi_failed_boolean_id',
            'currency' => 'IDR',
            'status' => 'pending',
            'transaction_status' => 'pending',
            'gross_amount' => 100000,
        ]);

        $payload = json_encode([
            'id' => 'evt_failed_boolean_id',
            'object' => 'event',
            'type' => 'payment_intent.payment_failed',
            'data' => [
                'object' => [
                    'id' => true,
                    'object' => 'payment_intent',
                    'status' => 'requires_payment_method',
                    'currency' => 'idr',
                    'amount' => 10000000,
                ],
            ],
        ]);

        $secret = 'whsec_test_secret';

        Config::set(
            'services.stripe.webhook_secret',
            $secret
        );

        $signature = WebhookSignature::generateSignatureHeader(
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

        $response->assertStatus(422);

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => 'pending',
            'transaction_status' => 'pending',
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'processing',
        ]);
    }

    public function test_stripe_webhook_does_not_cancel_payment_when_canceled_event_has_boolean_payment_intent_id(): void
    {
        $user = User::factory()->create();

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'processing',
        ]);

        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'provider' => 'stripe',
            'stripe_payment_intent_id' => 'pi_canceled_boolean_id',
            'currency' => 'IDR',
            'status' => 'pending',
            'transaction_status' => 'pending',
            'gross_amount' => 100000,
        ]);

        $payload = json_encode([
            'id' => 'evt_canceled_boolean_id',
            'object' => 'event',
            'type' => 'payment_intent.canceled',
            'data' => [
                'object' => [
                    'id' => true,
                    'object' => 'payment_intent',
                    'status' => 'canceled',
                    'currency' => 'idr',
                    'amount' => 10000000,
                ],
            ],
        ]);

        $secret = 'whsec_test_secret';

        Config::set(
            'services.stripe.webhook_secret',
            $secret
        );

        $signature = WebhookSignature::generateSignatureHeader(
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

        $response->assertStatus(422);

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => 'pending',
            'transaction_status' => 'pending',
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'processing',
        ]);
    }

    public function test_stripe_webhook_does_not_complete_payment_when_succeeded_event_has_object_payment_intent_id(): void
    {
        $user = User::factory()->create();

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'processing',
        ]);

        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'provider' => 'stripe',
            'stripe_payment_intent_id' => 'pi_succeeded_object_id',
            'currency' => 'IDR',
            'status' => 'pending',
            'transaction_status' => 'pending',
            'gross_amount' => 100000,
        ]);

        $payload = json_encode([
            'id' => 'evt_succeeded_object_id',
            'object' => 'event',
            'type' => 'payment_intent.succeeded',
            'data' => [
                'object' => [
                    'id' => [
                        'invalid' => 'id',
                    ],
                    'object' => 'payment_intent',
                    'status' => 'succeeded',
                    'currency' => 'idr',
                    'amount' => 10000000,
                ],
            ],
        ]);

        $secret = 'whsec_test_secret';

        Config::set(
            'services.stripe.webhook_secret',
            $secret
        );

        $signature = WebhookSignature::generateSignatureHeader(
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

        $response->assertStatus(422);

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => 'pending',
            'transaction_status' => 'pending',
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'processing',
        ]);
    }
}

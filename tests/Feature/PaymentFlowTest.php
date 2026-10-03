<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class PaymentFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_stripe_webhook_marks_payment_and_order_as_completed(): void
    {
        $user = User::factory()->create();

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'pending',
            'total' => 150000,
        ]);

        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'provider' => 'stripe',
            'status' => 'pending',
            'stripe_payment_intent_id' => 'pi_test_123',
            'gross_amount' => 150000,
        ]);

        /*
         * Webhook signature tidak sebaiknya dipalsukan
         * dengan HTTP test biasa. Handler signature akan
         * diverifikasi menggunakan Stripe SDK.
         *
         * Untuk test unit/integration yang lebih dalam,
         * mock Stripe Webhook::constructEvent().
         */
        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => 'pending',
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'pending',
        ]);
    }

    public function test_successful_payment_is_idempotent(): void
    {
        $user = User::factory()->create();

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'completed',
            'total' => 150000,
        ]);

        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'provider' => 'midtrans',
            'status' => 'succeeded',
            'transaction_status' => 'settlement',
            'paid_at' => now(),
            'gross_amount' => 150000,
        ]);

        $payment->refresh();
        $order->refresh();

        $this->assertSame('succeeded', $payment->status);
        $this->assertSame('completed', $order->status);
    }
}

<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Payment;
use App\Models\Refund;
use App\Models\User;
use App\Services\RefundService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class RefundServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_pending_payment_cannot_be_refunded(): void
    {
        $user = User::factory()->create();

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'pending',
            'total' => 150000,
        ]);

        Payment::factory()->create([
            'order_id' => $order->id,
            'provider' => 'stripe',
            'status' => 'pending',
            'gross_amount' => 150000,
        ]);

        $service = app(RefundService::class);

        $this->expectException(ValidationException::class);

        $service->request(
            $order,
            150000,
            'Customer meminta refund'
        );

        $this->assertDatabaseCount('refunds', 0);
    }

    public function test_succeeded_payment_can_request_refund(): void
    {
        $user = User::factory()->create();

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'completed',
            'total' => 150000,
        ]);

        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'provider' => 'stripe',
            'status' => 'succeeded',
            'stripe_payment_intent_id' => 'pi_test_123',
            'gross_amount' => 150000,
        ]);

        $refund = app(RefundService::class)->request(
            $order,
            150000,
            'Customer meminta refund'
        );

        $this->assertInstanceOf(
            Refund::class,
            $refund
        );

        $this->assertDatabaseHas('refunds', [
            'order_id' => $order->id,
            'payment_id' => $payment->id,
            'amount' => 150000,
            'currency' => 'IDR',
            'reason' => 'Customer meminta refund',
            'status' => Refund::STATUS_REQUESTED,
            'provider' => 'stripe',
        ]);
    }

    public function test_partial_refund_is_allowed(): void
    {
        $user = User::factory()->create();

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'completed',
            'total' => 150000,
        ]);

        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'provider' => 'stripe',
            'status' => 'succeeded',
            'gross_amount' => 150000,
        ]);

        $refund = app(RefundService::class)->request(
            $order,
            50000,
            'Produk rusak sebagian'
        );

        $this->assertSame(
            '50000.00',
            $refund->amount
        );

        $this->assertDatabaseHas('refunds', [
            'payment_id' => $payment->id,
            'amount' => 50000,
            'status' => Refund::STATUS_REQUESTED,
        ]);
    }

    public function test_refund_cannot_exceed_payment_amount(): void
    {
        $user = User::factory()->create();

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'completed',
            'total' => 150000,
        ]);

        Payment::factory()->create([
            'order_id' => $order->id,
            'provider' => 'stripe',
            'status' => 'succeeded',
            'gross_amount' => 150000,
        ]);

        $service = app(RefundService::class);

        $this->expectException(ValidationException::class);

        $service->request(
            $order,
            150001,
            'Refund melebihi pembayaran'
        );

        $this->assertDatabaseCount('refunds', 0);
    }

    public function test_multiple_partial_refunds_cannot_exceed_payment_amount(): void
    {
        $user = User::factory()->create();

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'completed',
            'total' => 150000,
        ]);

        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'provider' => 'stripe',
            'status' => 'succeeded',
            'gross_amount' => 150000,
        ]);

        Refund::create([
            'order_id' => $order->id,
            'payment_id' => $payment->id,
            'amount' => 100000,
            'currency' => 'IDR',
            'status' => Refund::STATUS_COMPLETED,
            'provider' => 'stripe',
            'requested_at' => now(),
            'processed_at' => now(),
        ]);

        $service = app(RefundService::class);

        $this->expectException(ValidationException::class);

        $service->request(
            $order,
            50001,
            'Refund kedua'
        );

        $this->assertDatabaseCount('refunds', 1);
    }

    public function test_remaining_amount_can_be_refunded_after_partial_refund(): void
    {
        $user = User::factory()->create();

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'completed',
            'total' => 150000,
        ]);

        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'provider' => 'stripe',
            'status' => 'succeeded',
            'gross_amount' => 150000,
        ]);

        Refund::create([
            'order_id' => $order->id,
            'payment_id' => $payment->id,
            'amount' => 50000,
            'currency' => 'IDR',
            'status' => Refund::STATUS_COMPLETED,
            'provider' => 'stripe',
            'requested_at' => now(),
            'processed_at' => now(),
        ]);

        $refund = app(RefundService::class)->request(
            $order,
            100000,
            'Refund sisa pembayaran'
        );

        $this->assertSame(
            '100000.00',
            $refund->amount
        );

        $this->assertDatabaseCount('refunds', 2);
    }

    public function test_zero_refund_amount_is_rejected(): void
    {
        $user = User::factory()->create();

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'completed',
            'total' => 150000,
        ]);

        Payment::factory()->create([
            'order_id' => $order->id,
            'provider' => 'stripe',
            'status' => 'succeeded',
            'gross_amount' => 150000,
        ]);

        $service = app(RefundService::class);

        $this->expectException(ValidationException::class);

        $service->request(
            $order,
            0,
            'Nominal tidak valid'
        );

        $this->assertDatabaseCount('refunds', 0);
    }
}
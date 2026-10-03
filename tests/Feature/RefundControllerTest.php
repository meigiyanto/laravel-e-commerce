<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Payment;
use App\Models\Refund;
use App\Models\User;
use App\Services\RefundService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RefundControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_request_refund(): void
    {
        $user = User::factory()->create();

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'completed',
        ]);

        $response = $this->post(
            route('orders.refund.store', $order),
            [
                'amount' => 50000,
            ]
        );

        $response->assertRedirect(
            route('login')
        );

        $this->assertDatabaseCount('refunds', 0);
    }

    public function test_customer_can_request_refund_for_own_order(): void
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

        $response = $this
            ->actingAs($user)
            ->post(
                route('orders.refund.store', $order),
                [
                    'amount' => 50000,
                    'reason' => 'Produk rusak',
                ]
            );

        $response
            ->assertRedirect(
                route('orders.show', $order)
            )
            ->assertSessionHas(
                'success',
                'Permintaan refund berhasil diajukan dan menunggu proses admin.'
            );

        $this->assertDatabaseHas('refunds', [
            'order_id' => $order->id,
            'payment_id' => $order->payment->id,
            'amount' => 50000,
            'reason' => 'Produk rusak',
            'status' => Refund::STATUS_REQUESTED,
            'provider' => 'stripe',
        ]);
    }

    public function test_customer_cannot_request_refund_for_another_customer_order(): void
    {
        $owner = User::factory()->create();

        $otherUser = User::factory()->create();

        $order = Order::factory()->create([
            'user_id' => $owner->id,
            'status' => 'completed',
            'total' => 150000,
        ]);

        Payment::factory()->create([
            'order_id' => $order->id,
            'provider' => 'stripe',
            'status' => 'succeeded',
            'gross_amount' => 150000,
        ]);

        $response = $this
            ->actingAs($otherUser)
            ->post(
                route('orders.refund.store', $order),
                [
                    'amount' => 50000,
                    'reason' => 'Refund',
                ]
            );

        $response->assertForbidden();

        $this->assertDatabaseCount('refunds', 0);
    }

    public function test_customer_cannot_request_refund_without_payment(): void
    {
        $user = User::factory()->create();

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'completed',
            'total' => 150000,
        ]);

        $response = $this
            ->actingAs($user)
            ->post(
                route('orders.refund.store', $order),
                [
                    'amount' => 50000,
                    'reason' => 'Tidak sesuai',
                ]
            );

        $response->assertSessionHasErrors([
            'payment',
        ]);

        $this->assertDatabaseCount('refunds', 0);
    }

    public function test_customer_cannot_request_refund_when_payment_is_not_succeeded(): void
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
            'status' => 'pending',
            'gross_amount' => 150000,
        ]);

        $response = $this
            ->actingAs($user)
            ->post(
                route('orders.refund.store', $order),
                [
                    'amount' => 50000,
                    'reason' => 'Payment belum selesai',
                ]
            );

        $response->assertSessionHasErrors([
            'payment',
        ]);

        $this->assertDatabaseCount('refunds', 0);
    }

    public function test_customer_cannot_request_refund_above_refundable_amount(): void
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

        $response = $this
            ->actingAs($user)
            ->post(
                route('orders.refund.store', $order),
                [
                    'amount' => 200000,
                    'reason' => 'Terlalu besar',
                ]
            );

        $response->assertSessionHasErrors([
            'amount',
        ]);

        $this->assertDatabaseCount('refunds', 0);
    }

    public function test_customer_cannot_request_zero_refund(): void
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

        $response = $this
            ->actingAs($user)
            ->post(
                route('orders.refund.store', $order),
                [
                    'amount' => 0,
                ]
            );

        $response->assertSessionHasErrors([
            'amount',
        ]);

        $this->assertDatabaseCount('refunds', 0);
    }

    public function test_customer_cannot_request_negative_refund(): void
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

        $response = $this
            ->actingAs($user)
            ->post(
                route('orders.refund.store', $order),
                [
                    'amount' => -10000,
                ]
            );

        $response->assertSessionHasErrors([
            'amount',
        ]);

        $this->assertDatabaseCount('refunds', 0);
    }

    public function test_customer_can_request_partial_refund(): void
    {
        $user = User::factory()->create();

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'completed',
            'total' => 150000,
        ]);

        Payment::factory()->create([
            'order_id' => $order->id,
            'provider' => 'midtrans',
            'status' => 'succeeded',
            'gross_amount' => 150000,
        ]);

        $response = $this
            ->actingAs($user)
            ->post(
                route('orders.refund.store', $order),
                [
                    'amount' => 25000,
                    'reason' => 'Satu produk rusak',
                ]
            );

        $response->assertRedirect(
            route('orders.show', $order)
        );

        $this->assertDatabaseHas('refunds', [
            'order_id' => $order->id,
            'amount' => 25000,
            'provider' => 'midtrans',
            'status' => Refund::STATUS_REQUESTED,
        ]);
    }

    public function test_customer_refund_controller_delegates_to_refund_service(): void
    {
        $user = User::factory()->create();

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'completed',
        ]);

        $refund = new Refund([
            'amount' => 50000,
            'currency' => 'IDR',
            'reason' => 'Produk rusak',
            'status' => Refund::STATUS_REQUESTED,
            'provider' => 'stripe',
        ]);

        $this->mock(
            RefundService::class,
            function ($mock) use ($order, $refund) {
                $mock
                    ->shouldReceive('request')
                    ->once()
                    ->withArgs(function (
                        Order $receivedOrder,
                        int|float|string $amount,
                        ?string $reason
                    ) use ($order) {
                        return $receivedOrder->is($order)
                            && (float) $amount === 50000.0
                            && $reason === 'Produk rusak';
                    })
                    ->andReturn($refund);
            }
        );

        $response = $this
            ->actingAs($user)
            ->post(
                route('orders.refund.store', $order),
                [
                    'amount' => 50000,
                    'reason' => 'Produk rusak',
                ]
            );

        $response
            ->assertRedirect(route('orders.show', $order))
            ->assertSessionHas(
                'success',
                'Permintaan refund berhasil diajukan dan menunggu proses admin.'
            );
    }
}
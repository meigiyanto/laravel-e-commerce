<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RefundControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_request_refund_for_own_order(): void
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
            'stripe_payment_intent_id' => 'pi_test_refund_123',
        ]);

        $response = $this
            ->actingAs($user)
            ->post(route('orders.refund.store', $order), [
                'amount' => 150000,
                'reason' => 'Customer meminta refund',
            ]);

        $response
            ->assertRedirect(route('orders.show', $order))
            ->assertSessionHas(
                'success',
                'Pengajuan refund berhasil dibuat dan sedang menunggu proses.'
            );

        $this->assertDatabaseHas('refunds', [
            'order_id' => $order->id,
            'amount' => 150000,
            'reason' => 'Customer meminta refund',
            'status' => 'requested',
            'provider' => 'stripe',
        ]);
    }

    public function test_user_cannot_request_refund_for_another_users_order(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $order = Order::factory()->create([
            'user_id' => $otherUser->id,
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
            ->post(route('orders.refund.store', $order), [
                'amount' => 150000,
                'reason' => 'Unauthorized refund',
            ]);

        $response->assertForbidden();

        $this->assertDatabaseCount('refunds', 0);
    }

    public function test_invalid_refund_amount_is_rejected(): void
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
            ->post(route('orders.refund.store', $order), [
                'amount' => 0,
                'reason' => 'Nominal tidak valid',
            ]);

        $response->assertSessionHasErrors('amount');

        $this->assertDatabaseCount('refunds', 0);
    }

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

        $response = $this
            ->actingAs($user)
            ->post(route('orders.refund.store', $order), [
                'amount' => 150000,
                'reason' => 'Refund pembayaran pending',
            ]);

        $response->assertSessionHasErrors();

        $this->assertDatabaseCount('refunds', 0);
    }
}

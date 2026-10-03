<?php

namespace Tests\Feature\Admin;

use App\Models\Order;
use App\Models\Payment;
use App\Models\Refund;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RefundControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_refund_list(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $order = Order::factory()->create([
            'user_id' => $admin->id,
            'order_number' => 'ORD-REFUND-001',
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
            'reason' => 'Produk rusak',
            'status' => Refund::STATUS_REQUESTED,
            'provider' => 'stripe',
            'requested_at' => now(),
        ]);

        $response = $this
            ->actingAs($admin)
            ->get(route('admin.refunds.index'));

        $response
            ->assertOk()
            ->assertViewIs('admin.refunds.index')
            ->assertSee('ORD-REFUND-001')
            ->assertSee('Rp 50.000')
            ->assertSee('Stripe')
            ->assertSee('Requested');
    }

    public function test_admin_can_view_refund_detail(): void
    {
        $admin = User::factory()->create([
           'role' => 'admin',
        ]);

        $order = Order::factory()->create([
            'user_id' => $admin->id,
            'order_number' => 'ORD-REFUND-002',
            'status' => 'completed',
            'total' => 150000,
        ]);

        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'provider' => 'stripe',
            'status' => 'succeeded',
            'gross_amount' => 150000,
        ]);

        $refund = Refund::create([
            'order_id' => $order->id,
            'payment_id' => $payment->id,
            'amount' => 75000,
            'currency' => 'IDR',
            'reason' => 'Barang tidak sesuai',
            'status' => Refund::STATUS_REQUESTED,
            'provider' => 'stripe',
            'requested_at' => now(),
        ]);

        $response = $this
            ->actingAs($admin)
            ->get(route('admin.refunds.show', $refund));

        $response
            ->assertOk()
            ->assertViewIs('admin.refunds.show')
            ->assertSee('Barang tidak sesuai')
            ->assertSee('Rp 75.000')
            ->assertSee('Requested');
    }

    public function test_non_admin_cannot_access_refund_management(): void
    {
        $user = User::factory()->create([
            'role' => 'user',
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('admin.refunds.index'));

        $response->assertForbidden();
    }

    public function test_admin_can_filter_refunds_by_status(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $order = Order::factory()->create([
            'user_id' => $admin->id,
            'status' => 'completed',
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
            'status' => Refund::STATUS_REQUESTED,
            'provider' => 'stripe',
            'requested_at' => now(),
        ]);

        Refund::create([
            'order_id' => $order->id,
            'payment_id' => $payment->id,
            'amount' => 25000,
            'currency' => 'IDR',
            'status' => Refund::STATUS_COMPLETED,
            'provider' => 'stripe',
            'requested_at' => now(),
            'processed_at' => now(),
        ]);

        $response = $this
            ->actingAs($admin)
            ->get(route('admin.refunds.index', [
                'status' => Refund::STATUS_REQUESTED,
            ]));

        $response
            ->assertOk()
            ->assertSee('Rp 50.000')
            ->assertDontSee('Rp 25.000');
    }
}
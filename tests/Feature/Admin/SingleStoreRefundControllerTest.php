<?php

namespace Tests\Feature\Admin;

use App\Models\Order;
use App\Models\Payment;
use App\Models\Refund;
use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SingleStoreRefundControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['operational.mode' => 'single_store']);

        app(TenantContext::class)->clear();
    }

    public function test_admin_can_process_requested_cod_refund_without_tenant(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $order = Order::factory()->create([
            'user_id' => $admin->id,
            'status' => 'completed',
        ]);

        $this->assertNull($order->tenant_id);

        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'provider' => 'cod',
            'status' => 'succeeded',
            'gross_amount' => 150000,
        ]);

        $refund = Refund::create([
            'order_id' => $order->id,
            'payment_id' => $payment->id,
            'amount' => 50000,
            'currency' => 'IDR',
            'status' => Refund::STATUS_REQUESTED,
            'provider' => 'cod',
            'requested_at' => now(),
        ]);

        $response = $this->actingAs($admin)->post(
            route('admin.refunds.process', $refund)
        );

        $response
            ->assertRedirect(route('admin.refunds.show', $refund))
            ->assertSessionHas('success', 'Refund berhasil diproses.');

        $this->assertDatabaseHas('refunds', [
            'id' => $refund->id,
            'status' => Refund::STATUS_APPROVED,
        ]);

        $this->assertNull($order->fresh()->tenant_id);
    }
}

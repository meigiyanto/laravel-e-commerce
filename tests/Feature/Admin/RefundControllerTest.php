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

    public function test_guest_cannot_access_refund_management(): void
    {
        $response = $this->get(
            route('admin.refunds.index')
        );

        $response->assertRedirect(
            route('admin.login')
        );
    }

    public function test_admin_can_view_refund_list(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $refund = $this->createRefund(
            admin: $admin,
            orderNumber: 'ORD-REFUND-001',
            amount: 50000,
            reason: 'Produk rusak',
            status: Refund::STATUS_REQUESTED,
        );

        $response = $this
            ->actingAs($admin)
            ->get(route('admin.refunds.index'));

        $response
            ->assertOk()
            ->assertViewIs('admin.refunds.index')
            ->assertSee($refund->order->order_number)
            ->assertSee('Rp 50.000')
            ->assertSee('Stripe')
            ->assertSee('Requested');
    }

    public function test_admin_can_view_refund_detail(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $refund = $this->createRefund(
            admin: $admin,
            orderNumber: 'ORD-REFUND-002',
            amount: 75000,
            reason: 'Barang tidak sesuai',
            status: Refund::STATUS_REQUESTED,
        );

        $response = $this
            ->actingAs($admin)
            ->get(
                route('admin.refunds.show', $refund)
            );

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

    public function test_non_admin_cannot_view_refund_detail(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $user = User::factory()->create([
            'role' => 'user',
        ]);

        $refund = $this->createRefund(
            admin: $admin,
            orderNumber: 'ORD-REFUND-003',
            amount: 50000,
            status: Refund::STATUS_REQUESTED,
        );

        $response = $this
            ->actingAs($user)
            ->get(
                route('admin.refunds.show', $refund)
            );

        $response->assertForbidden();
    }

    public function test_admin_can_filter_refunds_by_status(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $requested = $this->createRefund(
            admin: $admin,
            orderNumber: 'ORD-REQUESTED',
            amount: 50000,
            status: Refund::STATUS_REQUESTED,
        );

        $completed = $this->createRefund(
            admin: $admin,
            orderNumber: 'ORD-COMPLETED',
            amount: 25000,
            status: Refund::STATUS_COMPLETED,
        );

        $response = $this
            ->actingAs($admin)
            ->get(route('admin.refunds.index', [
                'status' => Refund::STATUS_REQUESTED,
            ]));

        $response
            ->assertOk()
            ->assertSee($requested->order->order_number)
            ->assertDontSee($completed->order->order_number);
    }

    public function test_admin_can_search_refunds_by_order_number(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $matched = $this->createRefund(
            admin: $admin,
            orderNumber: 'ORD-SEARCH-001',
            amount: 50000,
            status: Refund::STATUS_REQUESTED,
        );

        $notMatched = $this->createRefund(
            admin: $admin,
            orderNumber: 'ORD-OTHER-002',
            amount: 75000,
            status: Refund::STATUS_REQUESTED,
        );

        $response = $this
            ->actingAs($admin)
            ->get(route('admin.refunds.index', [
                'search' => 'ORD-SEARCH-001',
            ]));

        $response
            ->assertOk()
            ->assertSee($matched->order->order_number)
            ->assertDontSee($notMatched->order->order_number);
    }

    public function test_admin_can_search_refunds_by_customer_name(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $matched = $this->createRefund(
            admin: $admin,
            orderNumber: 'ORD-CUSTOMER-001',
            customerName: 'Budi Refund',
            amount: 50000,
        );

        $notMatched = $this->createRefund(
            admin: $admin,
            orderNumber: 'ORD-CUSTOMER-002',
            customerName: 'Andi Customer',
            amount: 75000,
        );

        $response = $this
            ->actingAs($admin)
            ->get(route('admin.refunds.index', [
                'search' => 'Budi Refund',
            ]));

        $response
            ->assertOk()
            ->assertSee($matched->order->order_number)
            ->assertDontSee($notMatched->order->order_number);
    }

    public function test_admin_can_process_requested_cod_refund(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $refund = $this->createRefund(
            admin: $admin,
            orderNumber: 'ORD-PROCESS-001',
            amount: 50000,
            provider: 'cod',
            paymentStatus: 'succeeded',
            status: Refund::STATUS_REQUESTED,
        );

        $response = $this
            ->actingAs($admin)
            ->post(
                route('admin.refunds.process', $refund)
            );

        $response
            ->assertRedirect(
                route('admin.refunds.show', $refund)
            )
            ->assertSessionHas(
                'success',
                'Refund berhasil diproses.'
            );

        $this->assertDatabaseHas('refunds', [
            'id' => $refund->id,
            'status' => Refund::STATUS_APPROVED,
        ]);
    }

    public function test_admin_cannot_process_non_requested_refund(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $refund = $this->createRefund(
            admin: $admin,
            orderNumber: 'ORD-PROCESS-002',
            amount: 50000,
            provider: 'cod',
            paymentStatus: 'succeeded',
            status: Refund::STATUS_COMPLETED,
        );

        $response = $this
            ->actingAs($admin)
            ->post(
                route('admin.refunds.process', $refund)
            );

        $response
            ->assertRedirect()
            ->assertSessionHas(
                'error',
                'Refund ini sudah diproses atau tidak dapat diproses kembali.'
            );

        $this->assertDatabaseHas('refunds', [
            'id' => $refund->id,
            'status' => Refund::STATUS_COMPLETED,
        ]);
    }

    public function test_non_admin_cannot_process_refund(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $user = User::factory()->create([
            'role' => 'user',
        ]);

        $refund = $this->createRefund(
            admin: $admin,
            orderNumber: 'ORD-PROCESS-003',
            amount: 50000,
            status: Refund::STATUS_REQUESTED,
        );

        $response = $this
            ->actingAs($user)
            ->post(
                route('admin.refunds.process', $refund)
            );

        $response->assertForbidden();

        $this->assertDatabaseHas('refunds', [
            'id' => $refund->id,
            'status' => Refund::STATUS_REQUESTED,
        ]);
    }

    public function test_admin_can_reject_requested_refund(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $refund = $this->createRefund(
            admin: $admin,
            orderNumber: 'ORD-REJECT-001',
            amount: 50000,
            status: Refund::STATUS_REQUESTED,
        );

        $response = $this
            ->actingAs($admin)
            ->post(
                route('admin.refunds.reject', $refund)
            );

        $response
            ->assertRedirect(
                route('admin.refunds.show', $refund)
            )
            ->assertSessionHas(
                'success',
                'Refund berhasil ditolak.'
            );

        $this->assertDatabaseHas('refunds', [
            'id' => $refund->id,
            'status' => Refund::STATUS_REJECTED,
        ]);

        $refund->refresh();

        $this->assertNotNull(
            $refund->processed_at
        );
    }

    public function test_admin_cannot_reject_non_requested_refund(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $refund = $this->createRefund(
            admin: $admin,
            orderNumber: 'ORD-REJECT-002',
            amount: 50000,
            status: Refund::STATUS_COMPLETED,
        );

        $response = $this
            ->actingAs($admin)
            ->post(
                route('admin.refunds.reject', $refund)
            );

        $response
            ->assertRedirect()
            ->assertSessionHas(
                'error',
                'Refund ini sudah diproses atau tidak dapat ditolak kembali.'
            );

        $this->assertDatabaseHas('refunds', [
            'id' => $refund->id,
            'status' => Refund::STATUS_COMPLETED,
        ]);
    }

    public function test_non_admin_cannot_reject_refund(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $user = User::factory()->create([
            'role' => 'user',
        ]);

        $refund = $this->createRefund(
            admin: $admin,
            orderNumber: 'ORD-REJECT-003',
            amount: 50000,
            status: Refund::STATUS_REQUESTED,
        );

        $response = $this
            ->actingAs($user)
            ->post(
                route('admin.refunds.reject', $refund)
            );

        $response->assertForbidden();

        $this->assertDatabaseHas('refunds', [
            'id' => $refund->id,
            'status' => Refund::STATUS_REQUESTED,
        ]);
    }

    private function createRefund(
        User $admin,
        string $orderNumber,
        float|int $amount,
        string $reason = 'Refund test',
        string $status = Refund::STATUS_REQUESTED,
        string $provider = 'stripe',
        string $paymentStatus = 'succeeded',
        string $customerName = 'Test Customer',
    ): Refund {
        $order = Order::factory()->create([
            'user_id' => $admin->id,
            'order_number' => $orderNumber,
            'status' => 'completed',
            'customer_name' => $customerName,
            'total' => 150000,
        ]);

        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'provider' => $provider,
            'status' => $paymentStatus,
            'gross_amount' => 150000,
        ]);

        return Refund::create([
            'order_id' => $order->id,
            'payment_id' => $payment->id,
            'amount' => $amount,
            'currency' => 'IDR',
            'reason' => $reason,
            'status' => $status,
            'provider' => $provider,
            'requested_at' => now(),
            'processed_at' => $status === Refund::STATUS_COMPLETED
                ? now()
                : null,
        ]);
    }
}

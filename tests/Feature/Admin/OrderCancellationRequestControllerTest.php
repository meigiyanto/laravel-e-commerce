<?php

namespace Tests\Feature\Admin;

use App\Models\Order;
use App\Models\OrderCancellationRequest;
use App\Models\Payment;
use App\Models\Tenant;
use App\Models\TenantMembership;
use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderCancellationRequestControllerTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        config(['operational.mode' => 'multi_tenant']);

        app(TenantContext::class)->clear();

        $this->tenant = Tenant::factory()->create([
            'name' => 'Default Tenant',
            'slug' => 'default',
            'status' => 'active',
        ]);

        app(TenantContext::class)->set($this->tenant);
    }

    private function createMember(
        string $membershipRole
    ): User {
        $user = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        TenantMembership::create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $user->id,
            'role' => $membershipRole,
            'status' => 'active',
            'accepted_at' => now(),
        ]);

        return $user;
    }

    private function createCancellationRequest(
        Tenant $tenant,
        string $paymentMethod = 'cod'
    ): array {
        $customer = User::factory()->create([
            'role' => User::ROLE_CUSTOMER,
        ]);

        $order = Order::factory()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $customer->id,
            'status' => 'pending',
            'total' => 100000,
        ]);

        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'provider' => $paymentMethod,
            'payment_method' => $paymentMethod,
            'status' => 'pending',
            'transaction_status' => 'pending',
            'gross_amount' => 100000,
        ]);

        $cancellationRequest = OrderCancellationRequest::create([
            'order_id' => $order->id,
            'user_id' => $customer->id,
            'status' => 'pending',
            'reason' => 'Customer requested cancellation.',
        ]);

        return [$order, $payment, $cancellationRequest];
    }

    private function decide(
        User $admin,
        OrderCancellationRequest $cancellationRequest,
        string $decision = 'approved'
    ) {
        return $this->actingAs($admin)->post(
            route('admin.order-cancellation-requests.decide', $cancellationRequest),
            [
                'decision' => $decision,
                'admin_note' => 'Regression test decision.',
            ]
        );
    }

    /**
     * Staff memiliki akses ke rute operasional,
     * tetapi tidak memiliki permission orders.manage.
     */
    public function test_staff_without_orders_manage_cannot_decide_cancellation_request(): void
    {
        $staff = $this->createMember(
            TenantMembership::ROLE_STAFF
        );

        [$order, $payment, $cancellationRequest] = $this->createCancellationRequest($this->tenant);

        $this->decide($staff, $cancellationRequest)->assertForbidden();

        $this->assertDatabaseHas('order_cancellation_requests', [
            'id' => $cancellationRequest->id,
            'status' => 'pending',
            'reviewed_by' => null,
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'pending',
        ]);

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => 'pending',
        ]);
    }

    /**
     * Admin tenant A tidak boleh melihat atau memutuskan
     * cancellation request milik tenant B.
     */
    public function test_admin_cannot_access_cancellation_request_from_another_tenant(): void
    {
        $admin = $this->createMember(
            TenantMembership::ROLE_ADMIN
        );
        
        $context = app(TenantContext::class);

        $otherTenant = Tenant::factory()->create([
            'status' => 'active',
        ]);
        
        $context->set($otherTenant);

        $this->assertSame($otherTenant->id, $context->require()->id);

        [$order, $payment, $cancellationRequest] = $this->createCancellationRequest($otherTenant);

        $context->set($this->tenant);

        $this->assertSame($this->tenant->id, $context->require()->id);

        $this->actingAs($admin)
            ->get(route(
                'admin.order-cancellation-requests.show',
                $cancellationRequest
            ))
            ->assertNotFound();

        $this->decide($admin, $cancellationRequest)->assertNotFound();

        $this->assertDatabaseHas('order_cancellation_requests', [
            'id' => $cancellationRequest->id,
            'status' => 'pending',
            'reviewed_by' => null,
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'pending',
        ]);

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => 'pending',
        ]);
    }

    /**
     * Menguji dua pengiriman keputusan terhadap request yang sama.
     *
     * Hanya satu keputusan yang boleh mengubah status order,
     * payment, dan cancellation request.
     */
    public function test_duplicate_concurrent_cancellation_decisions_are_idempotent(): void
    {
        $admin = $this->createMember(
            TenantMembership::ROLE_ADMIN
        );

        [$order, $payment, $cancellationRequest] = $this->createCancellationRequest($this->tenant);

        // Proses pertama menyetujui pembatalan.
        $this->decide($admin, $cancellationRequest)->assertRedirect();

        // Proses kedua mengirim keputusan untuk request yang sama.
        $this->decide($admin, $cancellationRequest)
            ->assertRedirect()
            ->assertSessionHas(
                'error',
                'Permintaan ini sudah ditinjau sebelumnya.'
            );

        $this->assertDatabaseHas('order_cancellation_requests', [
            'id' => $cancellationRequest->id,
            'status' => 'approved',
            'reviewed_by' => $admin->id,
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'canceled',
        ]);

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => 'canceled',
        ]);

        $this->assertSame(
            1,
            OrderCancellationRequest::query()
                ->where('order_id', $order->id)
                ->where('status', 'approved')
                ->count()
        );
    }
}

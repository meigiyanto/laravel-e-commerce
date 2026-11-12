<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SingleStoreSaasRestrictionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['operational.mode' => 'single_store']);
    }

    public function test_admin_cannot_open_tenant_domain_management(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.domains.store'), [
                'domain' => 'store.example.test',
            ])
            ->assertNotFound();
    }

    public function test_admin_cannot_open_subscription_management(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.subscription.index'))
            ->assertNotFound();
    }

    public function test_customer_cannot_accept_tenant_invitation_in_single_store(): void
    {
        $customer = User::factory()->create([
            'role' => User::ROLE_CUSTOMER,
            'email_verified_at' => now(),
        ]);

        $this->actingAs($customer)
            ->get('/tenant/invitations/fake-token')
            ->assertNotFound();
    }

    public function test_platform_admin_cannot_open_tenant_management_in_single_store(): void
    {
        $platformAdmin = User::factory()->create([
            'role' => User::ROLE_PLATFORM_ADMIN,
        ]);

        $this->actingAs($platformAdmin)
            ->get(route('platform.tenants.index'))
            ->assertNotFound();
    }

    public function test_platform_admin_cannot_consume_tenant_access_in_single_store(): void
    {
        $platformAdmin = User::factory()->create([
            'role' => User::ROLE_PLATFORM_ADMIN,
        ]);

        $this->actingAs($platformAdmin)
            ->get('/platform/tenant-access/fake-token')
            ->assertNotFound();
    }

    public function test_single_store_admin_can_still_open_platform_dashboard(): void
    {
        $platformAdmin = User::factory()->create([
            'role' => User::ROLE_PLATFORM_ADMIN,
        ]);

        $this->actingAs($platformAdmin)
            ->get(route('platform.dashboard'))
            ->assertOk();
    }
}

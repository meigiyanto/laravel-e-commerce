<?php

namespace Tests\Feature\Platform;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantDetailTest extends TestCase
{
    use RefreshDatabase;

    public function test_platform_admin_can_open_tenant_detail(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_PLATFORM_ADMIN,
        ]);

        $owner = User::factory()->create();

        $tenant = Tenant::create([
            'name' => 'Tenant Detail',
            'slug' => 'tenant-detail',
            'status' => 'active',
            'created_by_user_id' => $owner->id,
        ]);

        $this->actingAs($user)
            ->get(route('platform.tenants.show', $tenant))
            ->assertOk()
            ->assertViewIs('platform.tenants.show')
            ->assertViewHas('tenant', function ($result) use ($tenant) {
                return $result->is($tenant);
            });
    }

    public function test_regular_admin_cannot_open_tenant_detail(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $this->actingAs($user)
            ->get('/platform/tenants/1')
            ->assertForbidden();
    }

    public function test_guest_cannot_open_tenant_detail(): void
    {
        $this->get('/platform/tenants/1')
            ->assertRedirect(route('login'));
    }

    public function test_missing_tenant_returns_not_found(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_PLATFORM_ADMIN,
        ]);

        $this->actingAs($user)
            ->get('/platform/tenants/999999')
            ->assertNotFound();
    }
}

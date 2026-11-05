<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\TenantMembership;
use App\Models\User;
use App\Policies\TenantMembershipPolicy;
use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantRoleEscalationTest extends TestCase
{
    use RefreshDatabase;

    private function createTenant(string $slug): Tenant
    {
        $owner = User::factory()->create();

        return Tenant::create([
            'created_by_user_id' => $owner->id,
            'name' => $slug,
            'slug' => $slug,
            'status' => 'active',
        ]);
    }

    private function addMembership(
        Tenant $tenant,
        User $user,
        string $role
    ): TenantMembership {
        return TenantMembership::create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'role' => $role,
            'status' => 'active',
            'accepted_at' => now(),
        ]);
    }

    public function test_global_admin_role_does_not_grant_tenant_access(): void
    {
        $tenant = $this->createTenant('tenant-a');

        $user = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        app(TenantContext::class)->set($tenant);

        $this->assertFalse(
            $user->hasTenantRole(
                TenantMembership::ROLE_ADMIN,
                TenantMembership::ROLE_STAFF
            )
        );

        $this->assertFalse(
            $user->hasTenantPermission('users.manage')
        );
    }

    public function test_tenant_a_admin_cannot_access_tenant_b(): void
    {
        $tenantA = $this->createTenant('tenant-a');
        $tenantB = $this->createTenant('tenant-b');

        $admin = User::factory()->create();

        $this->addMembership(
            $tenantA,
            $admin,
            TenantMembership::ROLE_ADMIN
        );

        app(TenantContext::class)->set($tenantB);

        $this->assertFalse(
            $admin->hasTenantPermission('users.manage')
        );

        $this->assertFalse(
            $admin->hasTenantRole(TenantMembership::ROLE_ADMIN)
        );
    }

    public function test_tenant_admin_cannot_assign_owner_role(): void
    {
        $tenant = $this->createTenant('tenant-a');

        $admin = User::factory()->create();
        $target = User::factory()->create();

        $this->addMembership(
            $tenant,
            $admin,
            TenantMembership::ROLE_ADMIN
        );

        app(TenantContext::class)->set($tenant);

        $policy = app(TenantMembershipPolicy::class);

        $this->assertFalse(
            $policy->assignRole(
                $admin,
                $target,
                TenantMembership::ROLE_OWNER
            )
        );
    }

    public function test_tenant_admin_cannot_promote_user_to_admin(): void
    {
        $tenant = $this->createTenant('tenant-a');

        $admin = User::factory()->create();
        $target = User::factory()->create();

        $this->addMembership(
            $tenant,
            $admin,
            TenantMembership::ROLE_ADMIN
        );

        app(TenantContext::class)->set($tenant);

        $policy = app(TenantMembershipPolicy::class);

        $this->assertFalse(
            $policy->assignRole(
                $admin,
                $target,
                TenantMembership::ROLE_ADMIN
            )
        );
    }

    public function test_admin_cannot_change_own_membership_role(): void
    {
        $tenant = $this->createTenant('tenant-a');

        $admin = User::factory()->create();

        $this->addMembership(
            $tenant,
            $admin,
            TenantMembership::ROLE_ADMIN
        );

        app(TenantContext::class)->set($tenant);

        $policy = app(TenantMembershipPolicy::class);

        $this->assertFalse(
            $policy->assignRole(
                $admin,
                $admin,
                TenantMembership::ROLE_STAFF
            )
        );
    }

    public function test_inactive_membership_does_not_grant_access(): void
    {
        $tenant = $this->createTenant('tenant-a');

        $user = User::factory()->create();

        TenantMembership::create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'role' => TenantMembership::ROLE_ADMIN,
            'status' => 'inactive',
        ]);

        app(TenantContext::class)->set($tenant);

        $this->assertFalse(
            $user->hasTenantPermission('users.manage')
        );
    }
}
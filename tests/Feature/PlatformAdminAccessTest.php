<?php

namespace Tests\Feature;

use App\Models\PlatformAccessToken;
use App\Models\PlatformAuditLog;
use App\Models\Store;
use App\Models\StoreDomain;
use App\Models\Tenant;
use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PlatformAdminAccessTest extends TestCase
{
    use RefreshDatabase;

    private function createTenant(string $slug): Tenant
    {
        $owner = User::factory()->create();

        $tenant = Tenant::create([
            'name' => ucfirst($slug),
            'slug' => $slug,
            'status' => 'active',
            'created_by_user_id' => $owner->id,
        ]);

        $store = Store::create([
            'tenant_id' => $tenant->id,
            'name' => ucfirst($slug),
            'slug' => $slug,
            'status' => 'active',
        ]);

        StoreDomain::create([
            'store_id' => $store->id,
            'domain' => $slug.'.example.test',
            'verification_status' => 'verified',
            'is_primary' => true,
        ]);

        return $tenant;
    }

    public function test_platform_admin_can_open_platform_dashboard(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_PLATFORM_ADMIN,
        ]);

        $this->actingAs($user)
            ->get('/platform/dashboard')
            ->assertOk();
    }

    public function test_regular_admin_cannot_open_platform_dashboard(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $this->actingAs($user)
            ->get('/platform/dashboard')
            ->assertForbidden();
    }

    public function test_guest_is_redirected_from_platform_dashboard(): void
    {
        $this->get('/platform/dashboard')
            ->assertRedirect(route('login'));
    }

    public function test_platform_admin_can_request_tenant_access(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_PLATFORM_ADMIN,
        ]);

        $tenant = $this->createTenant('tenant-a');

        $response = $this->actingAs($user)
            ->post(route('platform.tenants.enter', $tenant));

        $response->assertRedirect();

        $this->assertDatabaseHas('platform_access_tokens', [
            'user_id' => $user->id,
            'tenant_id' => $tenant->id,
            'consumed_at' => null,
        ]);

        $this->assertDatabaseHas('platform_audit_logs', [
            'user_id' => $user->id,
            'tenant_id' => $tenant->id,
            'action' => 'tenant.access_requested',
        ]);
    }

    public function test_tenant_access_token_creates_session_and_audit_log(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_PLATFORM_ADMIN,
        ]);

        $tenant = $this->createTenant('tenant-a');
        $plainToken = Str::random(64);

        $accessToken = PlatformAccessToken::create([
            'token_hash' => hash('sha256', $plainToken),
            'user_id' => $user->id,
            'tenant_id' => $tenant->id,
            'expires_at' => now()->addMinutes(2),
        ]);

        $this->get(
            'http://tenant-a.example.test/platform/tenant-access/'.$plainToken
        )->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($user);

        $this->assertNotNull($accessToken->fresh()->consumed_at);

        $this->assertDatabaseHas('platform_audit_logs', [
            'user_id' => $user->id,
            'tenant_id' => $tenant->id,
            'action' => 'tenant.access_granted',
        ]);

        $this->assertFalse(app(TenantContext::class)->hasTenant());
    }

    public function test_tenant_access_token_cannot_be_reused(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_PLATFORM_ADMIN,
        ]);

        $tenant = $this->createTenant('tenant-a');
        $plainToken = Str::random(64);

        PlatformAccessToken::create([
            'token_hash' => hash('sha256', $plainToken),
            'user_id' => $user->id,
            'tenant_id' => $tenant->id,
            'expires_at' => now()->addMinutes(2),
            'consumed_at' => now(),
        ]);

        $this->get(
            'http://tenant-a.example.test/platform/tenant-access/'.$plainToken
        )->assertNotFound();
    }

    public function test_token_cannot_be_used_on_another_tenant_domain(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_PLATFORM_ADMIN,
        ]);

        $tenantA = $this->createTenant('tenant-a');
        $this->createTenant('tenant-b');

        $plainToken = Str::random(64);

        PlatformAccessToken::create([
            'token_hash' => hash('sha256', $plainToken),
            'user_id' => $user->id,
            'tenant_id' => $tenantA->id,
            'expires_at' => now()->addMinutes(2),
        ]);

        $this->get(
            'http://tenant-b.example.test/platform/tenant-access/'.$plainToken
        )->assertNotFound();
    }
}
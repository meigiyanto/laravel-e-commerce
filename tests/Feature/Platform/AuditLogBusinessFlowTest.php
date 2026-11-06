<?php

namespace Tests\Feature\Platform;

use App\Models\PlatformAccessToken;
use App\Models\PlatformAuditLog;
use App\Models\Store;
use App\Models\StoreDomain;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Platform\PlatformAuditLogger;
use App\Services\Tenancy\TenantLifecycleService;
use App\Services\Tenancy\TenantProvisioningService;
use App\Support\Platform\PlatformAuditAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class AuditLogBusinessFlowTest extends TestCase
{
    use RefreshDatabase;

    private function platformAdmin(): User
    {
        return User::factory()->create([
            'role' => User::ROLE_PLATFORM_ADMIN,
        ]);
    }

    private function tenantWithVerifiedDomain(
        string $domain = 'shop.example.test'
    ): Tenant {
        $tenant = Tenant::factory()->create([
            'status' => 'active',
        ]);

        $store = Store::create([
            'tenant_id' => $tenant->id,
            'name' => 'Test Store',
            'slug' => 'test-store-'.$tenant->id,
            'status' => 'active',
            'settings' => [],
        ]);

        StoreDomain::create([
            'store_id' => $store->id,
            'domain' => $domain,
            'type' => 'custom',
            'is_primary' => true,
            'verification_status' => 'verified',
            'verified_at' => now(),
        ]);

        return $tenant;
    }

    private function setAuditRequestContext(): void
    {
        $request = Request::create(
            '/platform/test',
            'POST',
            [],
            [],
            [],
            [
                'REMOTE_ADDR' => '203.0.113.10',
                'HTTP_USER_AGENT' => 'Audit Test Browser',
            ]
        );

        app()->instance(Request::class, $request);
    }

    public function test_provisioning_creates_audit_log(): void
    {
        $admin = $this->platformAdmin();

        $result = app(TenantProvisioningService::class)->provision(
            $admin,
            'Acme Tenant',
            'Acme Store',
            'owner@example.test'
        );

        $this->assertDatabaseHas('platform_audit_logs', [
            'user_id' => $admin->id,
            'tenant_id' => $result['tenant']->id,
            'action' => PlatformAuditAction::TENANT_PROVISIONED,
        ]);

        $log = PlatformAuditLog::query()
        ->where('tenant_id', $result['tenant']->id)
        ->where('action', PlatformAuditAction::TENANT_PROVISIONED)
        ->firstOrFail();

        $this->assertSame(
            (int) $result['store']->id,
            $log->metadata['store_id']
        );

        $this->assertSame('Acme Tenant', $log->metadata['tenant_name']);
        $this->assertSame('Acme Store', $log->metadata['store_name']);
        $this->assertArrayNotHasKey('owner_email', $log->metadata);
    }

    public function test_provisioning_rollback_does_not_leave_audit_log(): void
    {
        $admin = $this->platformAdmin();

        $logger = Mockery::mock(PlatformAuditLogger::class);
        $logger->shouldReceive('record')
        ->once()
        ->andThrow(new RuntimeException('Simulated audit failure'));

        $service = new TenantProvisioningService($logger);

        try {
            $service->provision(
                $admin,
                'Rollback Tenant',
                'Rollback Store',
                'owner@example.test'
            );

            $this->fail('Expected provisioning to fail.');
        } catch (RuntimeException $exception) {
            $this->assertSame(
                'Simulated audit failure',
                $exception->getMessage()
            );
        }

        $this->assertDatabaseMissing('tenants', [
            'name' => 'Rollback Tenant',
        ]);

        $this->assertDatabaseMissing('platform_audit_logs', [
            'action' => PlatformAuditAction::TENANT_PROVISIONED,
        ]);
    }

    public function test_status_change_records_previous_and_new_status(): void
    {
        $admin = $this->platformAdmin();
        $tenant = Tenant::factory()->create([
            'status' => 'pending',
        ]);

        app(TenantLifecycleService::class)->activate($tenant, $admin);

        $log = PlatformAuditLog::query()
        ->where('tenant_id', $tenant->id)
        ->where('action', PlatformAuditAction::TENANT_STATUS_CHANGED)
        ->firstOrFail();

        $this->assertSame('pending', $log->metadata['from']);
        $this->assertSame('active', $log->metadata['to']);
    }

    public function test_status_change_records_actor_and_request_context(): void
    {
        $this->setAuditRequestContext();

        $admin = $this->platformAdmin();
        $tenant = Tenant::factory()->create([
            'status' => 'pending',
        ]);

        app(TenantLifecycleService::class)->activate($tenant, $admin);

        $log = PlatformAuditLog::query()
        ->where('tenant_id', $tenant->id)
        ->where('action', PlatformAuditAction::TENANT_STATUS_CHANGED)
        ->firstOrFail();

        $this->assertSame($admin->id, $log->user_id);
        $this->assertSame('203.0.113.10', $log->ip_address);
        $this->assertSame('Audit Test Browser', $log->user_agent);
    }

    public function test_access_request_records_actor_and_domain(): void
    {
        $admin = $this->platformAdmin();
        $tenant = $this->tenantWithVerifiedDomain();

        $response = $this->actingAs($admin)->post(route('platform.tenants.enter', $tenant));
        
        $response->assertRedirect();
        
        $this->assertDatabaseHas('platform_audit_logs', [
            'user_id' => $admin->id,
            'tenant_id' => $tenant->id,
            'action' => PlatformAuditAction::TENANT_ACCESS_REQUESTED,
        ]);

        $log = PlatformAuditLog::query()
                    ->where('tenant_id', $tenant->id)
                    ->where('action', PlatformAuditAction::TENANT_ACCESS_REQUESTED)
                    ->firstOrFail();

        $this->assertSame(
            'shop.example.test',
            $log->metadata['domain']
        );
    }

    public function test_successful_token_consumption_creates_one_audit_log(): void
    {
        $admin = $this->platformAdmin();
        $tenant = $this->tenantWithVerifiedDomain();

        $plainToken = str_repeat('a', 64);

        $accessToken = PlatformAccessToken::create([
            'token_hash' => hash('sha256', $plainToken),
            'user_id' => $admin->id,
            'tenant_id' => $tenant->id,
            'expires_at' => now()->addMinutes(2),
        ]);

        $response = $this->get('http://shop.example.test/platform/tenant-access/'.$plainToken);
        
        $response->assertRedirect(route('admin.dashboard'));

        $this->assertDatabaseHas('platform_audit_logs', [
            'user_id' => $admin->id,
            'tenant_id' => $tenant->id,
            'action' => PlatformAuditAction::TENANT_ACCESS_GRANTED,
        ]);

        $logs = PlatformAuditLog::query()
                    ->where('action', PlatformAuditAction::TENANT_ACCESS_GRANTED)
                    ->where('tenant_id', $tenant->id)
                    ->get();

        $this->assertCount(1, $logs);
        $this->assertSame(
            (int) $accessToken->id,
            $logs->first()->metadata['access_token_id']
        );

        $this->assertArrayNotHasKey(
            'token',
            $logs->first()->metadata
        );
    }

    public function test_reused_token_does_not_create_granted_audit_log(): void
    {
        $admin = $this->platformAdmin();
        $tenant = $this->tenantWithVerifiedDomain();

        $plainToken = str_repeat('b', 64);

        PlatformAccessToken::create([
            'token_hash' => hash('sha256', $plainToken),
            'user_id' => $admin->id,
            'tenant_id' => $tenant->id,
            'expires_at' => now()->addMinutes(2),
            'consumed_at' => now(),
        ]);

        $this->get('http://shop.example.test/platform/tenant-access/'.$plainToken)->assertNotFound();

        $this->assertDatabaseMissing('platform_audit_logs', [
            'tenant_id' => $tenant->id,
            'action' => PlatformAuditAction::TENANT_ACCESS_GRANTED,
        ]);
    }

    public function test_cross_tenant_token_does_not_create_granted_audit_log(): void
    {
        $admin = $this->platformAdmin();

        $tenantA = $this->tenantWithVerifiedDomain('a.example.test');
        $this->tenantWithVerifiedDomain('b.example.test');

        $plainToken = str_repeat('c', 64);

        PlatformAccessToken::create([
            'token_hash' => hash('sha256', $plainToken),
            'user_id' => $admin->id,
            'tenant_id' => $tenantA->id,
            'expires_at' => now()->addMinutes(2),
        ]);

        $this->get('http://shop.example.test/platform/tenant-access/'.$plainToken)->assertNotFound();

        $this->assertDatabaseMissing('platform_audit_logs', [
            'tenant_id' => $tenantA->id,
            'action' => PlatformAuditAction::TENANT_ACCESS_GRANTED,
        ]);
    }

    public function test_unauthorized_platform_action_creates_no_success_audit_log(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_CUSTOMER,
        ]);

        $this->actingAs($user)->post(route('platform.tenants.provision'), [
            'tenant_name' => 'Unauthorized Tenant',
            'store_name' => 'Unauthorized Store',
            'owner_email' => 'owner@example.test',
        ])
        ->assertForbidden();

        $this->assertDatabaseMissing('tenants', [
            'name' => 'Unauthorized Tenant',
        ]);

        $this->assertDatabaseMissing('platform_audit_logs', [
            'action' => PlatformAuditAction::TENANT_PROVISIONED,
        ]);
    }

    public function test_audit_log_cannot_be_modified_or_deleted(): void
    {
        $admin = $this->platformAdmin();
        $tenant = Tenant::factory()->create();

        $log = PlatformAuditLog::create([
            'user_id' => $admin->id,
            'tenant_id' => $tenant->id,
            'action' => PlatformAuditAction::TENANT_STATUS_CHANGED,
            'metadata' => [
                'from' => 'pending',
                'to' => 'active',
            ],
        ]);

        try {
            $log->update(['action' => 'tampered']);
            $this->fail('Updating an audit log should be rejected.');
        } catch (\LogicException) {
            $this->assertDatabaseHas('platform_audit_logs', [
                'id' => $log->id,
                'action' => PlatformAuditAction::TENANT_STATUS_CHANGED,
            ]);
        }

        try {
            $log->delete();
            $this->fail('Deleting an audit log should be rejected.');
        } catch (\LogicException) {
            $this->assertDatabaseHas('platform_audit_logs', [
                'id' => $log->id,
                'action' => PlatformAuditAction::TENANT_STATUS_CHANGED,
            ]);
        }
    }
}
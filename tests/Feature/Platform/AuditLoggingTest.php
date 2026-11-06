<?php

namespace Tests\Feature\Platform;

use App\Models\PlatformAuditLog;
use App\Models\Store;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Platform\PlatformAuditLogger;
use App\Support\Platform\PlatformAuditAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use LogicException;
use Tests\TestCase;

class AuditLoggingTest extends TestCase
{
    use RefreshDatabase;

    public function test_provisioning_creates_audit_log(): void
    {
        $actor = User::factory()->create([
            'role' => User::ROLE_PLATFORM_ADMIN,
        ]);

        $this->actingAs($actor);

        $this->post(route('platform.tenants.provision'), [
            'tenant_name' => 'Audit Tenant',
            'store_name' => 'Audit Store',
            'owner_email' => 'owner@example.test',
        ])->assertRedirect();

        $tenant = Tenant::query()
            ->where('slug', 'audit-tenant')
            ->firstOrFail();

        $store = Store::query()
            ->where('tenant_id', $tenant->id)
            ->firstOrFail();

        $log = PlatformAuditLog::query()
            ->where('action', PlatformAuditAction::TENANT_PROVISIONED)
            ->firstOrFail();

        $this->assertSame($actor->id, $log->user_id);
        $this->assertSame($tenant->id, $log->tenant_id);
        $this->assertSame($store->id, $log->metadata['store_id']);
        $this->assertSame('Audit Tenant', $log->metadata['tenant_name']);
        $this->assertSame('Audit Store', $log->metadata['store_name']);
        $this->assertArrayNotHasKey('owner_email', $log->metadata);
        $this->assertArrayNotHasKey('token', $log->metadata);
        $this->assertArrayNotHasKey('token_hash', $log->metadata);
    }

    public function test_unknown_action_is_rejected(): void
    {
        $actor = User::factory()->create([
            'role' => User::ROLE_PLATFORM_ADMIN,
        ]);

        $this->expectException(InvalidArgumentException::class);

        app(PlatformAuditLogger::class)->record(
            $actor,
            'unknown.action',
            null,
        );
    }

    public function test_invalid_metadata_is_rejected(): void
    {
        $actor = User::factory()->create([
            'role' => User::ROLE_PLATFORM_ADMIN,
        ]);

        $tenant = Tenant::factory()->create();

        $this->expectException(InvalidArgumentException::class);

        app(PlatformAuditLogger::class)->record(
            $actor,
            PlatformAuditAction::TENANT_STATUS_CHANGED,
            $tenant,
            [
                'from' => 'active',
                'to' => 'invalid-status',
            ],
        );
    }

    public function test_unexpected_metadata_key_is_rejected(): void
    {
        $actor = User::factory()->create([
            'role' => User::ROLE_PLATFORM_ADMIN,
        ]);

        $tenant = Tenant::factory()->create();

        $this->expectException(InvalidArgumentException::class);

        app(PlatformAuditLogger::class)->record(
            $actor,
            PlatformAuditAction::TENANT_ACCESS_REQUESTED,
            $tenant,
            [
                'domain' => 'example.test',
                'token' => 'secret-token',
            ],
        );
    }

    public function test_audit_log_cannot_be_updated_or_deleted(): void
    {
        $actor = User::factory()->create();
        $tenant = Tenant::factory()->create();

        $log = PlatformAuditLog::create([
            'user_id' => $actor->id,
            'tenant_id' => $tenant->id,
            'action' => PlatformAuditAction::TENANT_ACCESS_REQUESTED,
            'metadata' => ['domain' => 'example.test'],
        ]);

        try {
            $log->update(['action' => 'modified.action']);
            $this->fail('Audit log update should be rejected.');
        } catch (LogicException) {
            $this->assertDatabaseHas('platform_audit_logs', [
                'id' => $log->id,
                'action' => PlatformAuditAction::TENANT_ACCESS_REQUESTED,
            ]);
        }

        try {
            $log->delete();
            $this->fail('Audit log deletion should be rejected.');
        } catch (LogicException) {
            $this->assertDatabaseHas('platform_audit_logs', [
                'id' => $log->id,
            ]);
        }
    }
}

<?php

namespace Tests\Feature\Platform;

use App\Models\PlatformAuditLog;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Platform\PlatformAuditLogger;
use App\Support\Platform\PlatformAuditAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use LogicException;
use Tests\TestCase;

class AuditLogSecurityTest extends TestCase
{
    use RefreshDatabase;

    private function createAuditLog(): PlatformAuditLog
    {
        $actor = User::factory()->create();
        $tenant = Tenant::factory()->create();

        return PlatformAuditLog::create([
            'user_id' => $actor->id,
            'tenant_id' => $tenant->id,
            'action' => PlatformAuditAction::TENANT_STATUS_CHANGED,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Test Browser',
            'metadata' => [
                'from' => 'pending',
                'to' => 'active',
            ],
        ]);
    }

    public function test_audit_log_cannot_be_updated(): void
    {
        $log = $this->createAuditLog();

        $this->expectException(LogicException::class);

        $log->update(['action' => 'tampered']);
    }

    public function test_audit_log_cannot_be_deleted(): void
    {
        $log = $this->createAuditLog();

        $this->expectException(LogicException::class);

        $log->delete();
    }

    public function test_mass_update_of_audit_logs_is_rejected(): void
    {
        $this->createAuditLog();

        $this->expectException(LogicException::class);

        PlatformAuditLog::query()->update([
            'action' => 'tampered',
        ]);
    }

    public function test_mass_delete_of_audit_logs_is_rejected(): void
    {
        $this->createAuditLog();

        $this->expectException(LogicException::class);

        PlatformAuditLog::query()->delete();
    }

    public function test_sensitive_metadata_is_not_persisted(): void
    {
        $actor = User::factory()->create();
        $tenant = Tenant::factory()->create();

        $request = Request::create('/platform/tenants', 'POST');
        $request->headers->set('User-Agent', 'Audit Test Browser');

        $logger = new PlatformAuditLogger($request);

        $log = $logger->record(
            actor: $actor,
            action: PlatformAuditAction::TENANT_ACCESS_REQUESTED,
            tenant: $tenant,
            metadata: [
                'domain' => 'shop.example.com',
            ],
        );

        $this->assertSame($actor->id, $log->user_id);
        $this->assertSame($tenant->id, $log->tenant_id);
        $this->assertSame(
            ['domain' => 'shop.example.com'],
            $log->metadata
        );
        $this->assertSame('Audit Test Browser', $log->user_agent);
    }

    public function test_user_agent_is_limited_to_500_characters(): void
    {
        $actor = User::factory()->create();
        $tenant = Tenant::factory()->create();

        $request = Request::create('/platform/tenants', 'POST');
        $request->headers->set('User-Agent', str_repeat('A', 1000));

        $logger = new PlatformAuditLogger($request);

        $log = $logger->record(
            actor: $actor,
            action: PlatformAuditAction::TENANT_ACCESS_REQUESTED,
            tenant: $tenant,
            metadata: [
                'domain' => 'shop.example.com',
            ],
        );

        $this->assertSame(500, mb_strlen($log->user_agent));
    }


    public function test_database_rejects_direct_audit_log_update(): void
    {
        $log = $this->createAuditLog();

        try {
            DB::table('platform_audit_logs')
            ->where('id', $log->id)
            ->update(['action' => 'tampered']);

            $this->fail('Database should reject audit log updates.');
        } catch (QueryException $exception) {
            $this->assertStringContainsString(
                'immutable',
                strtolower($exception->getMessage())
            );
        }

        $this->assertDatabaseHas('platform_audit_logs', [
            'id' => $log->id,
            'action' => PlatformAuditAction::TENANT_STATUS_CHANGED,
        ]);
    }

    public function test_database_rejects_direct_audit_log_delete(): void
    {
        $log = $this->createAuditLog();

        try {
            DB::table('platform_audit_logs')
            ->where('id', $log->id)
            ->delete();

            $this->fail('Database should reject audit log deletes.');
        } catch (QueryException $exception) {
            $this->assertStringContainsString(
                'immutable',
                strtolower($exception->getMessage())
            );
        }

        $this->assertDatabaseHas('platform_audit_logs', [
            'id' => $log->id,
        ]);
    }

    public function test_database_allows_new_audit_log_inserts(): void
    {
        $log = $this->createAuditLog();

        $this->assertDatabaseHas('platform_audit_logs', [
            'id' => $log->id,
            'action' => PlatformAuditAction::TENANT_STATUS_CHANGED,
        ]);
    }

    public function test_database_restricts_deleting_referenced_user(): void
    {
        $log = $this->createAuditLog();

        try {
            DB::table('users')
            ->where('id', $log->user_id)
            ->delete();

            $this->fail('Database should restrict deleting audit actors.');
        } catch (QueryException $exception) {
            $this->assertDatabaseHas('platform_audit_logs', [
                'id' => $log->id,
                'user_id' => $log->user_id,
            ]);
        }
    }


    public function test_database_triggers_are_registered(): void
    {
        $driver = DB::connection()->getDriverName();

        $this->assertSame('testing', app()->environment());
        $this->assertTrue(app()->runningUnitTests());

        $triggers = match ($driver) {
            'sqlite' => DB::select("
            SELECT name
            FROM sqlite_master
            WHERE type = 'trigger'
            AND tbl_name = 'platform_audit_logs'
        "),
            'mysql',
            'mariadb' => DB::select("
            SELECT TRIGGER_NAME AS name
            FROM information_schema.TRIGGERS
            WHERE TRIGGER_SCHEMA = DATABASE()
            AND EVENT_OBJECT_TABLE = ?
        ", ['platform_audit_logs']),
            default => $this->markTestSkipped(
                "Trigger inspection is not implemented for {$driver}."
            ),
        };

        $names = collect($triggers)->pluck('name')->all();

        $this->assertContains('platform_audit_logs_prevent_update', $names);
        $this->assertContains('platform_audit_logs_prevent_delete', $names);
    }

}
<?php

namespace Tests\Feature\Tenancy;

use App\Models\Tenant;
use App\Models\TenantAuditLog;
use App\Models\User;
use App\Services\Tenancy\TenantAuditLogger;
use App\Support\Tenancy\TenantAuditAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use LogicException;
use Tests\TestCase;

class TenantAuditLoggerTest extends TestCase
{
    use RefreshDatabase;

    private function createContext(): array
    {
        return [
            User::factory()->create(),
            Tenant::factory()->create(),
        ];
    }

    public function test_it_records_tenant_audit_log(): void
    {
        [$actor, $tenant] = $this->createContext();

        $log = app(TenantAuditLogger::class)->record(
            actor: $actor,
            tenant: $tenant,
            action: TenantAuditAction::DOMAIN_CREATED,
            subject: $tenant,
            metadata: [
                'store_domain_id' => 10,
                'domain' => 'shop.example.test',
                'type' => 'custom',
            ],
        );

        $this->assertDatabaseHas('tenant_audit_logs', [
            'id' => $log->id,
            'tenant_id' => $tenant->id,
            'user_id' => $actor->id,
            'action' => TenantAuditAction::DOMAIN_CREATED,
            'subject_type' => $tenant->getMorphClass(),
            'subject_id' => $tenant->id,
        ]);

        $this->assertSame('shop.example.test', $log->metadata['domain']);
    }

    public function test_unknown_action_is_rejected(): void
    {
        [$actor, $tenant] = $this->createContext();

        $this->expectException(InvalidArgumentException::class);

        app(TenantAuditLogger::class)->record(
            $actor,
            $tenant,
            'unknown.action',
            $tenant,
        );
    }

    public function test_missing_metadata_is_rejected(): void
    {
        [$actor, $tenant] = $this->createContext();

        $this->expectException(InvalidArgumentException::class);

        app(TenantAuditLogger::class)->record(
            $actor,
            $tenant,
            TenantAuditAction::DOMAIN_CREATED,
            $tenant,
            ['domain' => 'shop.example.test'],
        );
    }

    public function test_unexpected_metadata_is_rejected(): void
    {
        [$actor, $tenant] = $this->createContext();

        $this->expectException(InvalidArgumentException::class);

        app(TenantAuditLogger::class)->record(
            $actor,
            $tenant,
            TenantAuditAction::DOMAIN_CREATED,
            $tenant,
            [
                'store_domain_id' => 10,
                'domain' => 'shop.example.test',
                'type' => 'custom',
                'token' => 'secret-token',
            ],
        );
    }

    public function test_invalid_metadata_values_are_rejected(): void
    {
        [$actor, $tenant] = $this->createContext();

        $this->expectException(InvalidArgumentException::class);

        app(TenantAuditLogger::class)->record(
            $actor,
            $tenant,
            TenantAuditAction::INVENTORY_STOCK_UPDATED,
            $tenant,
            [
                'product_id' => 1,
                'variant_id' => 1,
                'from' => 10,
                'to' => -5,
                'reserved_quantity' => 0,
            ],
        );
    }

    public function test_audit_log_cannot_be_updated_or_deleted(): void
    {
        [$actor, $tenant] = $this->createContext();

        $log = app(TenantAuditLogger::class)->record(
            $actor,
            $tenant,
            TenantAuditAction::DOMAIN_CREATED,
            $tenant,
            [
                'store_domain_id' => 10,
                'domain' => 'shop.example.test',
                'type' => 'custom',
            ],
        );

        try {
            $log->update(['action' => 'modified.action']);
            $this->fail('Audit log update should be rejected.');
        } catch (LogicException) {
            $this->assertDatabaseHas('tenant_audit_logs', [
                'id' => $log->id,
                'action' => TenantAuditAction::DOMAIN_CREATED,
            ]);
        }

        try {
            $log->delete();
            $this->fail('Audit log deletion should be rejected.');
        } catch (LogicException) {
            $this->assertDatabaseHas('tenant_audit_logs', [
                'id' => $log->id,
            ]);
        }
    }

    public function test_audit_log_is_rolled_back_with_transaction(): void
    {
        [$actor, $tenant] = $this->createContext();

        try {
            DB::transaction(function () use ($actor, $tenant) {
                app(TenantAuditLogger::class)->record(
                    $actor,
                    $tenant,
                    TenantAuditAction::DOMAIN_CREATED,
                    $tenant,
                    [
                        'store_domain_id' => 10,
                        'domain' => 'shop.example.test',
                        'type' => 'custom',
                    ],
                );

                throw new \RuntimeException('Force rollback');
            });
        } catch (\RuntimeException) {
            // Expected rollback.
        }

        $this->assertDatabaseCount('tenant_audit_logs', 0);
    }
}
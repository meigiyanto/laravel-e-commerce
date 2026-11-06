<?php

namespace Tests\Feature\Platform;

use App\Models\PlatformAuditLog;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditLogViewerTest extends TestCase
{
    use RefreshDatabase;

    private function platformAdmin(): User
    {
        return User::factory()->create([
            'role' => User::ROLE_PLATFORM_ADMIN,
        ]);
    }

    private function createLog(
        User $actor,
        Tenant $tenant,
        string $action = 'tenant.provisioned',
        array $metadata = [],
        mixed $createdAt = null,
    ): PlatformAuditLog {
        $log = new PlatformAuditLog();

        $log->forceFill([
            'user_id' => $actor->id,
            'tenant_id' => $tenant->id,
            'action' => $action,
            'ip_address' => '203.0.113.10',
            'user_agent' => 'Audit Viewer Test',
            'metadata' => $metadata,
            'created_at' => $createdAt ?? now(),
        ]);

        $log->save();

        return $log;
    }

    public function test_guest_cannot_access_audit_logs(): void
    {
        $this->get(route('platform.audit-logs.index'))
        ->assertRedirect();
    }

    public function test_non_platform_admin_cannot_access_audit_logs(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_CUSTOMER,
        ]);

        $this->actingAs($user)
        ->get(route('platform.audit-logs.index'))
        ->assertForbidden();
    }

    public function test_platform_admin_can_view_audit_logs(): void
    {
        $admin = $this->platformAdmin();
        $actor = User::factory()->create();
        $tenant = Tenant::factory()->create();

        $this->createLog($actor, $tenant);

        $this->actingAs($admin)
        ->get(route('platform.audit-logs.index'))
        ->assertOk()
        ->assertViewIs('platform.audit-logs.index');
    }

    public function test_all_filters_can_be_applied_together(): void
    {
        $admin = $this->platformAdmin();
        $actor = User::factory()->create();
        $tenant = Tenant::factory()->create();

        $matching = $this->createLog(
            $actor,
            $tenant,
            'tenant.status_changed',
            ['marker' => 'MATCHING_LOG'],
            now()->subDays(2),
        );

        $this->createLog(
            User::factory()->create(),
            Tenant::factory()->create(),
            'tenant.provisioned',
            ['marker' => 'OTHER_LOG'],
            now()->subDays(2),
        );

        $response = $this->actingAs($admin)->get(
            route('platform.audit-logs.index', [
                'action' => 'tenant.status_changed',
                'tenant_id' => $tenant->id,
                'user_id' => $actor->id,
                'from' => now()->subDays(3)->toDateString(),
                'to' => now()->subDays(1)->toDateString(),
            ])
        );

        $response->assertOk()
        ->assertViewHas('logs', function ($logs) use ($matching) {
            return $logs->total() === 1 && $logs->contains('id', $matching->id);
        });
    }

    public function test_date_filter_includes_both_boundary_dates(): void
    {
        $admin = $this->platformAdmin();
        $actor = User::factory()->create();
        $tenant = Tenant::factory()->create();

        $start = $this->createLog($actor, $tenant, metadata: [
            'marker' => 'START_DATE',
        ], createdAt: now()->subDays(2)->startOfDay());

        $end = $this->createLog($actor, $tenant, metadata: [
            'marker' => 'END_DATE',
        ], createdAt: now()->subDay()->endOfDay());

        $outside = $this->createLog($actor, $tenant, metadata: [
            'marker' => 'OUTSIDE_DATE',
        ], createdAt: now()->subDays(4));

        $response = $this->actingAs($admin)->get(route('platform.audit-logs.index', [
            'from' => now()->subDays(2)->toDateString(),
            'to' => now()->subDay()->toDateString(),
        ]));

        $response->assertOk()
        ->assertViewHas('logs', function ($logs) use ($start, $end, $outside) {
            $ids = $logs->getCollection()->modelKeys();

            return $logs->total() === 2 && in_array($start->id, $ids, true) && in_array($end->id, $ids, true) && ! in_array($outside->id, $ids, true);
        });
    }

    public function test_invalid_filters_are_rejected(): void
    {
        $admin = $this->platformAdmin();

        $this->actingAs($admin)
        ->get(route('platform.audit-logs.index', [
            'action' => 'tenant.unknown',
        ]))
        ->assertSessionHasErrors('action');

        $this->actingAs($admin)
        ->get(route('platform.audit-logs.index', [
            'from' => '2026-11-10',
            'to' => '2026-11-01',
        ]))
        ->assertSessionHasErrors('to');
    }

    public function test_audit_logs_are_paginated_and_keep_filters(): void
    {
        $admin = $this->platformAdmin();
        $actor = User::factory()->create();
        $tenant = Tenant::factory()->create();

        foreach (range(1, 26) as $number) {
            $this->createLog($actor, $tenant, metadata: [
                'number' => $number,
            ]);
        }

        $response = $this->actingAs($admin)->get(
            route('platform.audit-logs.index', [
                'action' => 'tenant.provisioned',
            ])
        );

        $response->assertOk()
        ->assertViewHas('logs', function ($logs) {
            return $logs->total() === 26 && $logs->count() === 25 && $logs->currentPage() === 1;
        })
        ->assertSee('action=tenant.provisioned', false);

        $this->actingAs($admin)
        ->get(route('platform.audit-logs.index', [
            'action' => 'tenant.provisioned',
            'page' => 2,
        ]))
        ->assertViewHas('logs', function ($logs) {
            return $logs->total() === 26 && $logs->count() === 1 && $logs->currentPage() === 2;
        });
    }

    public function test_actor_and_tenant_relationships_are_displayed(): void
    {
        $admin = $this->platformAdmin();
        $actor = User::factory()->create([
            'name' => 'Audit Actor Name',
            'email' => 'audit-actor@example.test',
        ]);
        $tenant = Tenant::factory()->create([
            'name' => 'Audit Tenant Name',
        ]);

        $this->createLog($actor, $tenant);

        $this->actingAs($admin)
        ->get(route('platform.audit-logs.index'))
        ->assertOk()
        ->assertSee('Audit Actor Name')
        ->assertSee('audit-actor@example.test')
        ->assertSee('Audit Tenant Name');
    }

    public function test_detail_page_displays_metadata_safely(): void
    {
        $admin = $this->platformAdmin();
        $actor = User::factory()->create();
        $tenant = Tenant::factory()->create();

        $log = $this->createLog(
            $actor,
            $tenant,
            'tenant.access_granted',
            [
                'domain' => 'shop.example.test',
                'access_token_id' => 42,
                'extra' => [
                    'source' => 'platform',
                    'verified' => true,
                ],
                'untrusted' => '<script>alert(1)</script>',
            ],
        );

        $this->actingAs($admin)
        ->get(route('platform.audit-logs.show', $log))
        ->assertOk()
        ->assertViewIs('platform.audit-logs.show')
        ->assertSee('shop.example.test')
        ->assertSee('42')
        ->assertSee('platform')
        ->assertSee('true')
        ->assertSee('&lt;script&gt;', false)
        ->assertDontSee('<script>alert(1)</script>', false);
    }
}
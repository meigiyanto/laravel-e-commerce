<?php

namespace Tests\Feature\Platform;

use App\Models\Tenant;
use App\Models\User;
use App\Models\Store;
use App\Models\StoreDomain;
use App\Models\TenantInvitation;
use App\Models\TenantMembership;
use App\Models\Invoice;
use Illuminate\Support\Facades\DB;
use App\Models\SubscriptionPlan;
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

        $owner = User::factory()->create();

        $tenant = Tenant::create([
            'name' => 'Tenant Detail',
            'slug' => 'tenant-detail',
            'status' => 'active',
            'created_by_user_id' => $owner->id,
        ]);

        $this->actingAs($user)
        ->get(route('platform.tenants.show', $tenant))
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

    public function test_tenant_detail_displays_overview_information(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_PLATFORM_ADMIN,
        ]);

        $owner = User::factory()->create();

        $tenant = Tenant::create([
            'name' => 'Tenant Overview',
            'slug' => 'tenant-overview',
            'status' => 'active',
            'created_by_user_id' => $owner->id,
        ]);

        $this->actingAs($admin)
        ->get(route('platform.tenants.show', $tenant))
        ->assertOk()
        ->assertSee('Tenant Overview')
        ->assertSee($owner->name)
        ->assertSee('Tenant Information')
        ->assertSee('Billing &amp; Invoices', false);
    }

    public function test_tenant_detail_displays_verified_domains_and_prioritizes_primary_domain(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_PLATFORM_ADMIN]);
        $owner = User::factory()->create();

        $tenant = Tenant::create([
            'name' => 'Domain Tenant',
            'slug' => 'domain-tenant',
            'status' => 'active',
            'created_by_user_id' => $owner->id,
        ]);

        $store = Store::create([
            'tenant_id' => $tenant->id,
            'name' => 'Domain Store',
            'slug' => 'domain-store',
            'status' => 'active',
        ]);

        StoreDomain::create([
            'store_id' => $store->id,
            'domain' => 'secondary.example.test',
            'type' => 'custom',
            'is_primary' => false,
            'verification_status' => 'verified',
            'verified_at' => now(),
        ]);

        StoreDomain::create([
            'store_id' => $store->id,
            'domain' => 'primary.example.test',
            'type' => 'custom',
            'is_primary' => true,
            'verification_status' => 'verified',
            'verified_at' => now(),
        ]);

        StoreDomain::create([
            'store_id' => $store->id,
            'domain' => 'pending.example.test',
            'type' => 'custom',
            'is_primary' => false,
            'verification_status' => 'pending',
        ]);

        $response = $this->actingAs($admin)
        ->get(route('platform.tenants.show', $tenant));

        $response->assertOk()
        ->assertSee('primary.example.test')
        ->assertSee('secondary.example.test')
        ->assertDontSee('pending.example.test');

        $html = $response->getContent();

        $this->assertLessThan(
            strpos($html, 'secondary.example.test'),
            strpos($html, 'primary.example.test')
        );
    }

    public function test_tenant_detail_displays_only_active_memberships(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_PLATFORM_ADMIN]);
        $owner = User::factory()->create();
        $activeUser = User::factory()->create([
            'name' => 'Active Tenant Member',
            'email' => 'active-member@example.test',
        ]);
        $inactiveUser = User::factory()->create([
            'name' => 'Inactive Tenant Member',
            'email' => 'inactive-member@example.test',
        ]);

        $tenant = Tenant::create([
            'name' => 'Membership Tenant',
            'slug' => 'membership-tenant',
            'status' => 'active',
            'created_by_user_id' => $owner->id,
        ]);

        TenantMembership::create([
            'tenant_id' => $tenant->id,
            'user_id' => $activeUser->id,
            'role' => TenantMembership::ROLE_ADMIN,
            'status' => 'active',
            'accepted_at' => now(),
        ]);

        TenantMembership::create([
            'tenant_id' => $tenant->id,
            'user_id' => $inactiveUser->id,
            'role' => TenantMembership::ROLE_STAFF,
            'status' => 'suspended',
            'accepted_at' => now(),
        ]);

        $this->actingAs($admin)
        ->get(route('platform.tenants.show', $tenant))
        ->assertOk()
        ->assertSee('Active Tenant Member')
        ->assertSee('active-member@example.test')
        ->assertSee('Admin')
        ->assertDontSee('Inactive Tenant Member')
        ->assertDontSee('inactive-member@example.test');
    }

    public function test_tenant_detail_displays_invitations_without_exposing_token_hash(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_PLATFORM_ADMIN]);
        $owner = User::factory()->create();
        $inviter = User::factory()->create([
            'name' => 'Invitation Sender',
        ]);

        $tenant = Tenant::create([
            'name' => 'Invitation Tenant',
            'slug' => 'invitation-tenant',
            'status' => 'active',
            'created_by_user_id' => $owner->id,
        ]);

        $tokenHash = hash('sha256', 'secret-invitation-token');

        TenantInvitation::create([
            'tenant_id' => $tenant->id,
            'invited_by' => $inviter->id,
            'email' => 'invited-user@example.test',
            'role' => TenantMembership::ROLE_OWNER,
            'token_hash' => $tokenHash,
            'status' => 'pending',
            'expires_at' => now()->addDays(2),
        ]);

        $response = $this->actingAs($admin)
        ->get(route('platform.tenants.show', $tenant));

        $response->assertOk()
        ->assertSee('invited-user@example.test')
        ->assertSee('Invitation Sender')
        ->assertSee('Pending')
        ->assertDontSee($tokenHash);
    }

    public function test_tenant_detail_displays_only_its_invoice_history(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_PLATFORM_ADMIN]);
        $owner = User::factory()->create();

        $tenant = Tenant::create([
            'name' => 'Billing Tenant',
            'slug' => 'billing-tenant',
            'status' => 'active',
            'created_by_user_id' => $owner->id,
        ]);

        $otherTenant = Tenant::create([
            'name' => 'Other Billing Tenant',
            'slug' => 'other-billing-tenant',
            'status' => 'active',
            'created_by_user_id' => $owner->id,
        ]);

        $plan = SubscriptionPlan::create([
            'name' => 'Basic',
            'slug' => 'basic-test',
            'price_monthly' => 99000,
            'price_yearly' => 990000,
            'currency' => 'IDR',
            'trial_days' => 0,
            'is_active' => true,
        ]);

        $subscriptionId = DB::table('subscriptions')->insertGetId([
            'tenant_id' => $tenant->id,
            'subscription_plan_id' => $plan->id,
            'status' => 'active',
            'billing_cycle' => 'monthly',
            'currency' => 'IDR',
            'amount' => 99000,
            'starts_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $otherSubscriptionId = DB::table('subscriptions')->insertGetId([
            'tenant_id' => $otherTenant->id,
            'subscription_plan_id' => $plan->id,
            'status' => 'active',
            'billing_cycle' => 'monthly',
            'currency' => 'IDR',
            'amount' => 99000,
            'starts_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Invoice::create([
            'tenant_id' => $tenant->id,
            'subscription_id' => $subscriptionId,
            'invoice_number' => 'INV-TENANT-001',
            'status' => 'open',
            'currency' => 'IDR',
            'subtotal' => 99000,
            'tax_amount' => 0,
            'total' => 99000,
            'issued_at' => now(),
            'due_at' => now()->addDays(3),
        ]);

        Invoice::create([
            'tenant_id' => $otherTenant->id,
            'subscription_id' => $otherSubscriptionId,
            'invoice_number' => 'INV-OTHER-001',
            'status' => 'open',
            'currency' => 'IDR',
            'subtotal' => 99000,
            'tax_amount' => 0,
            'total' => 99000,
            'issued_at' => now(),
            'due_at' => now()->addDays(3),
        ]);

        $response = $this->actingAs($admin)->get(route('platform.tenants.show', $tenant));

        $response->assertOk()
        ->assertSee('INV-TENANT-001')
        ->assertDontSee('INV-OTHER-001')
        ->assertViewHas('tenant', function ($result) {
            return $result->invoices_count === 1;
        });
    }

    public function test_expired_invitations_are_not_counted_as_pending(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_PLATFORM_ADMIN]);
        $owner = User::factory()->create();

        $tenant = Tenant::create([
            'name' => 'Expired Invitation Tenant',
            'slug' => 'expired-invitation-tenant',
            'status' => 'active',
            'created_by_user_id' => $owner->id,
        ]);

        TenantInvitation::create([
            'tenant_id' => $tenant->id,
            'invited_by' => $admin->id,
            'email' => 'valid-invitation@example.test',
            'role' => TenantMembership::ROLE_OWNER,
            'token_hash' => hash('sha256', 'valid-token'),
            'status' => 'pending',
            'expires_at' => now()->addDay(),
        ]);

        TenantInvitation::create([
            'tenant_id' => $tenant->id,
            'invited_by' => $admin->id,
            'email' => 'expired-invitation@example.test',
            'role' => TenantMembership::ROLE_OWNER,
            'token_hash' => hash('sha256', 'expired-token'),
            'status' => 'pending',
            'expires_at' => now()->subDay(),
        ]);

        $this->actingAs($admin)
        ->get(route('platform.tenants.show', $tenant))
        ->assertOk()
        ->assertViewHas('tenant', function ($result) {
            return $result->pending_invitations_count === 1;
        });
    }

    public function test_expired_pending_invitation_is_displayed_as_expired(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_PLATFORM_ADMIN,
        ]);

        $owner = User::factory()->create();
        $inviter = User::factory()->create();

        $tenant = Tenant::create([
            'name' => 'Tenant Invitation Status',
            'slug' => 'tenant-invitation-status',
            'status' => 'active',
            'created_by_user_id' => $owner->id,
        ]);

        TenantInvitation::create([
            'tenant_id' => $tenant->id,
            'invited_by' => $inviter->id,
            'email' => 'expired@example.test',
            'role' => 'staff',
            'token_hash' => hash('sha256', 'expired-token'),
            'status' => 'pending',
            'expires_at' => now()->subDay(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        TenantInvitation::create([
            'tenant_id' => $tenant->id,
            'invited_by' => $inviter->id,
            'email' => 'valid@example.test',
            'role' => 'staff',
            'token_hash' => hash('sha256', 'valid-token'),
            'status' => 'pending',
            'expires_at' => now()->addDay(),
            'created_at' => now()->subMinute(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($admin)->get(route('platform.tenants.show', $tenant));

        $response->assertOk()
        ->assertViewHas('tenant', function ($result) {
            return $result->pending_invitations_count === 1;
        })
        ->assertSee('expired@example.test')
        ->assertSee('Expired')
        ->assertSee('valid@example.test')
        ->assertSee('Pending');
    }

    public function test_enter_tenant_ignores_unverified_primary_domain(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_PLATFORM_ADMIN]);
        $owner = User::factory()->create();

        $tenant = Tenant::create([
            'name' => 'Unverified Primary Tenant',
            'slug' => 'unverified-primary-tenant',
            'status' => 'active',
            'created_by_user_id' => $owner->id,
        ]);

        $store = Store::create([
            'tenant_id' => $tenant->id,
            'name' => 'Unverified Primary Store',
            'slug' => 'unverified-primary-store',
            'status' => 'active',
        ]);

        StoreDomain::create([
            'store_id' => $store->id,
            'domain' => 'unverified-primary.example.test',
            'type' => 'custom',
            'is_primary' => true,
            'verification_status' => 'pending',
        ]);

        StoreDomain::create([
            'store_id' => $store->id,
            'domain' => 'verified-secondary.example.test',
            'type' => 'custom',
            'is_primary' => false,
            'verification_status' => 'verified',
            'verified_at' => now(),
        ]);

        $response = $this->actingAs($admin)->post(route('platform.tenants.enter', $tenant));
        $targetUrl = $response->getTargetUrl();
        $path = parse_url($targetUrl, PHP_URL_PATH);
        
        $this->assertSame('verified-secondary.example.test', parse_url($targetUrl, PHP_URL_HOST));
        $this->assertStringStartsWith('/platform/tenant-access/', $path);
    }

    public function test_enter_tenant_selects_first_verified_primary_domain_when_multiple_exist(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_PLATFORM_ADMIN]);
        $owner = User::factory()->create();

        $tenant = Tenant::create([
            'name' => 'Multiple Primary Tenant',
            'slug' => 'multiple-primary-tenant',
            'status' => 'active',
            'created_by_user_id' => $owner->id,
        ]);

        $store = Store::create([
            'tenant_id' => $tenant->id,
            'name' => 'Multiple Primary Store',
            'slug' => 'multiple-primary-store',
            'status' => 'active',
        ]);

        StoreDomain::create([
            'store_id' => $store->id,
            'domain' => 'first-primary.example.test',
            'type' => 'custom',
            'is_primary' => true,
            'verification_status' => 'verified',
            'verified_at' => now(),
        ]);

        StoreDomain::create([
            'store_id' => $store->id,
            'domain' => 'second-primary.example.test',
            'type' => 'custom',
            'is_primary' => true,
            'verification_status' => 'verified',
            'verified_at' => now(),
        ]);

        $response = $this->actingAs($admin)->post(route('platform.tenants.enter', $tenant));
        $targetUrl = $response->getTargetUrl();
        $path = parse_url($targetUrl, PHP_URL_PATH);
        
        $this->assertSame('first-primary.example.test', parse_url($targetUrl, PHP_URL_HOST));
        $this->assertStringStartsWith('/platform/tenant-access/', $path);
    }

    public function test_tenant_detail_paginates_active_memberships_without_changing_total_count(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_PLATFORM_ADMIN]);
        $owner = User::factory()->create();

        $tenant = Tenant::create([
            'name' => 'Paginated Membership Tenant',
            'slug' => 'paginated-membership-tenant',
            'status' => 'active',
            'created_by_user_id' => $owner->id,
        ]);

        foreach (range(1, 12) as $number) {
            $user = User::factory()->create([
                'name' => "Membership User {$number}",
                'email' => "membership{$number}@example.test",
            ]);

            TenantMembership::create([
                'tenant_id' => $tenant->id,
                'user_id' => $user->id,
                'role' => TenantMembership::ROLE_STAFF,
                'status' => 'active',
                'accepted_at' => now(),
            ]);
        }

        $this->actingAs($admin)
        ->get(route('platform.tenants.show', $tenant).'?memberships_page=2')
        ->assertOk()
        ->assertViewHas('memberships', function ($memberships) {
            return $memberships->total() === 12 && $memberships->count() === 2 && $memberships->currentPage() === 2;
        })
        ->assertViewHas('tenant', function ($result) {
            return $result->active_memberships_count === 12;
        });
    }

    public function test_tenant_detail_paginates_invitations_without_changing_total_count(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_PLATFORM_ADMIN]);
        $owner = User::factory()->create();
        $inviter = User::factory()->create();

        $tenant = Tenant::create([
            'name' => 'Paginated Invitation Tenant',
            'slug' => 'paginated-invitation-tenant',
            'status' => 'active',
            'created_by_user_id' => $owner->id,
        ]);

        foreach (range(1, 12) as $number) {
            TenantInvitation::create([
                'tenant_id' => $tenant->id,
                'invited_by' => $inviter->id,
                'email' => "invitation{$number}@example.test",
                'role' => TenantMembership::ROLE_STAFF,
                'token_hash' => hash('sha256', "invitation-token-{$number}"),
                'status' => 'pending',
                'expires_at' => now()->addDays(2),
            ]);
        }

        $this->actingAs($admin)
        ->get(route('platform.tenants.show', $tenant).'?invitations_page=2')
        ->assertOk()
        ->assertViewHas('invitations', function ($invitations) {
            return $invitations->total() === 12 && $invitations->count() === 2 && $invitations->currentPage() === 2;
        })
        ->assertViewHas('tenant', function ($result) {
            return $result->pending_invitations_count === 12;
        });
    }
}
<?php

namespace Tests\Feature\Tenancy;

use App\Models\Category;
use App\Models\Product;
use App\Models\Store;
use App\Models\StoreDomain;
use App\Models\SubCategory;
use App\Models\Tenant;
use App\Models\TenantMembership;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class FoundationTest extends TestCase
{
    use RefreshDatabase;

    private function createTenant(User $owner, string $name, string $slug): Tenant
    {
        return Tenant::create(['name' => $name, 'slug' => $slug, 'status' => 'active', 'created_by_user_id' => $owner->id]);
    }

    public function test_tenant_store_membership_and_domain_relations_work(): void
    {
        $owner = User::factory()->create();
        $tenant = $this->createTenant($owner, 'Tenant A', 'tenant-a');
        $store = Store::create(['tenant_id' => $tenant->id, 'name' => 'Store A', 'slug' => 'store-a', 'status' => 'active']);
        $membership = TenantMembership::create(['tenant_id' => $tenant->id, 'user_id' => $owner->id, 'role' => TenantMembership::ROLE_OWNER, 'status' => 'active']);
        $domain = StoreDomain::create(['store_id' => $store->id, 'domain' => 'shop-a.example.test', 'verification_status' => 'verified']);

        $this->assertTrue($tenant->creator->is($owner));
        $this->assertTrue($tenant->store->is($store));
        $this->assertTrue($tenant->memberships->first()->is($membership));
        $this->assertTrue($store->domains->first()->is($domain));
        $this->assertTrue($owner->tenants->contains($tenant));
    }

    public function test_tenancy_tables_and_ownership_columns_exist(): void
    {
        foreach (['tenants', 'tenant_memberships', 'stores', 'store_domains'] as $table) {
            $this->assertTrue(Schema::hasTable($table), "Missing table {$table}");
        }
        foreach (['categories', 'sub_categories', 'products', 'product_variants', 'attributes', 'attribute_values', 'inventories', 'inventory_movements', 'inventory_reservations'] as $table) {
            $this->assertTrue(Schema::hasColumn($table, 'tenant_id'), "Missing {$table}.tenant_id");
        }
        $this->assertTrue(Schema::hasColumn('product_variant_attribute_values', 'tenant_id'));
    }

    public function test_same_category_slug_can_exist_in_different_tenants(): void
    {
        config(['operational.mode' => 'multi_tenant']);
        $tenantA = $this->createTenant(User::factory()->create(), 'Tenant A', 'tenant-a');
        $tenantB = $this->createTenant(User::factory()->create(), 'Tenant B', 'tenant-b');
        Category::create(['tenant_id' => $tenantA->id, 'name' => 'Shoes', 'slug' => 'shoes']);
        Category::create(['tenant_id' => $tenantB->id, 'name' => 'Shoes', 'slug' => 'shoes']);
        $this->assertDatabaseCount('categories', 2);
    }

    public function test_default_tenant_bootstrap_is_idempotent_and_preserves_existing_owned_catalog(): void
    {
        $owner = User::factory()->create();
        $this->assertSame(0, Artisan::call('tenancy:bootstrap-default', ['--owner-id' => $owner->id]));
        $tenant = Tenant::where('slug', 'default')->firstOrFail();
        $category = Category::create(['tenant_id' => $tenant->id, 'name' => 'Shoes', 'slug' => 'shoes']);
        $subCategory = SubCategory::create(['tenant_id' => $tenant->id, 'category_id' => $category->id, 'name' => 'Sneakers', 'slug' => 'sneakers']);
        $product = Product::create(['tenant_id' => $tenant->id, 'category_id' => $category->id, 'sub_category_id' => $subCategory->id, 'name' => 'Daily Sneaker', 'slug' => 'daily-sneaker', 'price' => 100000, 'stock' => 5]);

        $this->assertSame(0, Artisan::call('tenancy:bootstrap-default', ['--owner-id' => $owner->id]));
        $this->assertSame(1, Tenant::where('slug', 'default')->count());
        $this->assertSame(1, Store::where('tenant_id', $tenant->id)->count());
        $this->assertSame(1, TenantMembership::where('tenant_id', $tenant->id)->where('user_id', $owner->id)->count());
        $this->assertDatabaseHas('categories', ['id' => $category->id, 'tenant_id' => $tenant->id]);
        $this->assertDatabaseHas('sub_categories', ['id' => $subCategory->id, 'tenant_id' => $tenant->id]);
        $this->assertDatabaseHas('products', ['id' => $product->id, 'tenant_id' => $tenant->id]);
    }

    public function test_bootstrap_does_not_reassign_existing_tenant_to_another_owner(): void
    {
        $ownerA = User::factory()->create();
        $ownerB = User::factory()->create();
        $this->assertSame(0, Artisan::call('tenancy:bootstrap-default', ['--owner-id' => $ownerA->id]));
        $this->assertSame(1, Artisan::call('tenancy:bootstrap-default', ['--owner-id' => $ownerB->id]));
        $this->assertSame($ownerA->id, Tenant::where('slug', 'default')->value('created_by_user_id'));
    }

    public function test_bootstrap_requires_an_existing_owner(): void
    {
        $this->assertSame(1, Artisan::call('tenancy:bootstrap-default'));
        $this->assertDatabaseCount('tenants', 0);
    }

    public function test_active_memberships_only_returns_active_members(): void
    {
        $owner = User::factory()->create();
        $activeUser = User::factory()->create();
        $pendingUser = User::factory()->create();

        $tenant = $this->createTenant($owner, 'Tenant A', 'tenant-a');

        $activeMembership = TenantMembership::create([
            'tenant_id' => $tenant->id,
            'user_id' => $activeUser->id,
            'role' => TenantMembership::ROLE_STAFF,
            'status' => 'active',
        ]);

        TenantMembership::create([
            'tenant_id' => $tenant->id,
            'user_id' => $pendingUser->id,
            'role' => TenantMembership::ROLE_STAFF,
            'status' => 'pending',
        ]);

        $memberships = $tenant->activeMemberships()->get();

        $this->assertCount(1, $memberships);
        $this->assertTrue($memberships->first()->is($activeMembership));
    }

    public function test_verified_domains_only_returns_verified_domains(): void
    {
        $owner = User::factory()->create();
        $tenant = $this->createTenant($owner, 'Tenant A', 'tenant-a');

        $store = Store::create([
            'tenant_id' => $tenant->id,
            'name' => 'Store A',
            'slug' => 'store-a',
            'status' => 'active',
        ]);

        $verifiedDomain = StoreDomain::create([
            'store_id' => $store->id,
            'domain' => 'verified.example.test',
            'verification_status' => 'verified',
        ]);

        StoreDomain::create([
            'store_id' => $store->id,
            'domain' => 'pending.example.test',
            'verification_status' => 'pending',
        ]);

        $domains = $store->verifiedDomains()->get();

        $this->assertCount(1, $domains);
        $this->assertTrue($domains->first()->is($verifiedDomain));
    }
}
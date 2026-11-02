<?php

namespace Tests\Feature;

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

class TenancyFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_store_membership_and_domain_relations_work(): void
    {
        $owner = User::factory()->create();

        $tenant = Tenant::create([
            'name' => 'Tenant A',
            'slug' => 'tenant-a',
            'status' => 'active',
            'created_by_user_id' => $owner->id,
        ]);

        $store = Store::create([
            'tenant_id' => $tenant->id,
            'name' => 'Store A',
            'slug' => 'store-a',
            'status' => 'active',
        ]);

        $membership = TenantMembership::create([
            'tenant_id' => $tenant->id,
            'user_id' => $owner->id,
            'role' => TenantMembership::ROLE_OWNER,
            'status' => 'active',
        ]);

        $domain = StoreDomain::create([
            'store_id' => $store->id,
            'domain' => 'shop-a.example.test',
        ]);

        $this->assertTrue($tenant->creator->is($owner));
        $this->assertTrue($tenant->store->is($store));
        $this->assertTrue($tenant->memberships->first()->is($membership));
        $this->assertTrue($store->domains->first()->is($domain));
        $this->assertTrue($owner->tenants->contains($tenant));
    }

    public function test_tenancy_tables_and_ownership_columns_exist(): void
    {
        foreach ([
            'tenants',
            'tenant_memberships',
            'stores',
            'store_domains',
        ] as $table) {
            $this->assertTrue(
                Schema::hasTable($table),
                "Missing table {$table}"
            );
        }

        foreach ([
            'categories',
            'sub_categories',
            'products',
            'product_variants',
            'attributes',
            'inventories',
            'inventory_movements',
            'inventory_reservations',
        ] as $table) {
            $this->assertTrue(
                Schema::hasColumn($table, 'tenant_id'),
                "Missing {$table}.tenant_id"
            );
        }
    }

    public function test_same_category_slug_can_exist_in_different_tenants(): void
    {
        $ownerA = User::factory()->create();
        $ownerB = User::factory()->create();

        $tenantA = Tenant::create([
            'name' => 'Tenant A',
            'slug' => 'tenant-a',
            'status' => 'active',
            'created_by_user_id' => $ownerA->id,
        ]);

        $tenantB = Tenant::create([
            'name' => 'Tenant B',
            'slug' => 'tenant-b',
            'status' => 'active',
            'created_by_user_id' => $ownerB->id,
        ]);

        Category::create([
            'tenant_id' => $tenantA->id,
            'name' => 'Shoes',
            'slug' => 'shoes',
        ]);

        Category::create([
            'tenant_id' => $tenantB->id,
            'name' => 'Shoes',
            'slug' => 'shoes',
        ]);

        $this->assertDatabaseCount('categories', 2);
    }

    public function test_default_tenant_bootstrap_is_idempotent_and_backfills_catalog(): void
    {
        $owner = User::factory()->create();

        $category = Category::create([
            'name' => 'Shoes',
            'slug' => 'shoes',
        ]);

        $subCategory = SubCategory::create([
            'category_id' => $category->id,
            'name' => 'Sneakers',
            'slug' => 'sneakers',
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'sub_category_id' => $subCategory->id,
            'name' => 'Daily Sneaker',
            'slug' => 'daily-sneaker',
            'price' => 100000,
            'stock' => 5,
        ]);

        $arguments = ['--owner-id' => $owner->id];

        $this->assertSame(
            0,
            Artisan::call('tenancy:bootstrap-default', $arguments)
        );

        $this->assertSame(
            0,
            Artisan::call('tenancy:bootstrap-default', $arguments)
        );

        $tenant = Tenant::where('slug', 'default')->firstOrFail();

        $this->assertSame(1, Tenant::where('slug', 'default')->count());
        $this->assertSame(1, Store::where('tenant_id', $tenant->id)->count());

        $this->assertSame(
            1,
            TenantMembership::where('tenant_id', $tenant->id)
                ->where('user_id', $owner->id)
                ->count()
        );

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'tenant_id' => $tenant->id,
        ]);

        $this->assertDatabaseHas('sub_categories', [
            'id' => $subCategory->id,
            'tenant_id' => $tenant->id,
        ]);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'tenant_id' => $tenant->id,
        ]);
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
}
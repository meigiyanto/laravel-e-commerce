<?php

namespace Tests\Feature\Tenancy;

use App\Models\Category;
use App\Models\Product;
use App\Models\Store;
use App\Models\StoreDomain;
use App\Models\SubCategory;
use App\Models\Tenant;
use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TwoTenantStorefrontIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['operational.mode' => 'multi_tenant']);

        app(TenantContext::class)->clear();
    }

    private function createTenantStore(string $name, string $domain): array
    {
        $owner = User::factory()->create();

        $tenant = Tenant::create([
            'name' => $name,
            'slug' => str($name)->slug().'-'.$owner->id,
            'status' => 'active',
            'created_by_user_id' => $owner->id,
        ]);

        $store = Store::create([
            'tenant_id' => $tenant->id,
            'name' => $name,
            'slug' => str($name)->slug().'-'.$owner->id,
            'status' => 'active',
        ]);

        StoreDomain::create([
            'store_id' => $store->id,
            'domain' => $domain,
            'type' => 'custom',
            'is_primary' => true,
            'verification_status' => 'verified',
            'verified_at' => now(),
        ]);

        return [$tenant, $store];
    }

    private function createTenantCatalog(
        Tenant $tenant,
        string $name,
        string $slug
    ): array {
        return app(TenantContext::class)->run(
            $tenant,
            function () use ($tenant, $name, $slug) {
                $category = Category::create([
                    'tenant_id' => $tenant->id,
                    'name' => $name.' Category',
                    'slug' => $slug.'-category',
                ]);

                $subCategory = SubCategory::create([
                    'tenant_id' => $tenant->id,
                    'category_id' => $category->id,
                    'name' => $name.' Subcategory',
                    'slug' => $slug.'-subcategory',
                ]);

                $product = Product::create([
                    'tenant_id' => $tenant->id,
                    'category_id' => $category->id,
                    'sub_category_id' => $subCategory->id,
                    'name' => $name.' Product',
                    'slug' => $slug.'-product',
                    'price' => 100000,
                    'stock' => 10,
                ]);

                return [$category, $subCategory, $product];
            }
        );
    }

    public function test_each_verified_domain_displays_only_its_own_catalog(): void
    {
        [$tenantA] = $this->createTenantStore(
            'Store A',
            'store-a.example.test'
        );

        [$tenantB] = $this->createTenantStore(
            'Store B',
            'store-b.example.test'
        );

        [, , $productA] = $this->createTenantCatalog(
            $tenantA,
            'Alpha',
            'alpha'
        );

        [, , $productB] = $this->createTenantCatalog(
            $tenantB,
            'Beta',
            'beta'
        );

        $context = app(TenantContext::class);

        // Domain A harus menampilkan produk A saja.
        $responseA = $this->get('http://store-a.example.test/');

        $responseA->assertOk();
        $responseA->assertSee($productA->name);
        $responseA->assertDontSee($productB->name);

        $this->assertNull($context->current());

        // Domain B harus menampilkan produk B saja.
        $responseB = $this->get('http://store-b.example.test/');

        $responseB->assertOk();
        $responseB->assertSee($productB->name);
        $responseB->assertDontSee($productA->name);

        $this->assertNull($context->current());
    }

    public function test_shop_page_is_isolated_between_verified_domains(): void
    {
        [$tenantA] = $this->createTenantStore(
            'Store A',
            'store-a.example.test'
        );

        [$tenantB] = $this->createTenantStore(
            'Store B',
            'store-b.example.test'
        );

        [, , $productA] = $this->createTenantCatalog(
            $tenantA,
            'Alpha',
            'alpha'
        );

        [, , $productB] = $this->createTenantCatalog(
            $tenantB,
            'Beta',
            'beta'
        );

        $this->get('http://store-a.example.test/shop')
            ->assertOk()
            ->assertSee($productA->name)
            ->assertDontSee($productB->name);

        $this->assertNull(app(TenantContext::class)->current());

        $this->get('http://store-b.example.test/shop')
            ->assertOk()
            ->assertSee($productB->name)
            ->assertDontSee($productA->name);

        $this->assertNull(app(TenantContext::class)->current());
    }

    public function test_tenant_cannot_open_another_tenants_product_or_category(): void
    {
        [$tenantA] = $this->createTenantStore(
            'Store A',
            'store-a.example.test'
        );

        [$tenantB] = $this->createTenantStore(
            'Store B',
            'store-b.example.test'
        );

        [, , $productB] = $this->createTenantCatalog(
            $tenantB,
            'Beta',
            'beta'
        );

        [$categoryB] = $this->createTenantCatalog(
            $tenantB,
            'Gamma',
            'gamma'
        );

        // Domain A tidak boleh membuka detail produk tenant B.
        $this->get(
            'http://store-a.example.test/shop/'.$productB->slug
        )->assertNotFound();

        // Domain A tidak boleh membuka kategori tenant B.
        $this->get(
            'http://store-a.example.test/category/'.$categoryB->slug
        )->assertNotFound();

        // Domain B tetap dapat membuka resource miliknya.
        $this->get(
            'http://store-b.example.test/shop/'.$productB->slug
        )->assertOk();

        $this->get(
            'http://store-b.example.test/category/'.$categoryB->slug
        )->assertOk();

        $this->assertNull(app(TenantContext::class)->current());
    }
}

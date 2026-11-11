<?php

namespace Tests\Feature\Tenancy;

use App\Http\Middleware\ResolveTenantFromDomain;
use App\Models\Category;
use App\Models\Product;
use App\Models\Store;
use App\Models\StoreDomain;
use App\Models\SubCategory;
use App\Models\Tenant;
use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use LogicException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\TestCase;
use Illuminate\Database\QueryException;

class CatalogIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['operational.mode' => 'multi_tenant']);
        $tenant = Tenant::factory()->create();
        app(TenantContext::class)->set($tenant);
    }

    private function createTenant(string $name, string $slug): Tenant
    {
        return Tenant::create([
            'name' => $name,
            'slug' => $slug,
            'status' => 'active',
            'created_by_user_id' => User::factory()->create()->id,
        ]);
    }

    private function createCategory(Tenant $tenant, string $name): Category
    {
        return Category::create([
            'tenant_id' => $tenant->id,
            'name' => $name,
            'slug' => strtolower(str_replace(' ', '-', $name)),
        ]);
    }

    private function createProduct(Tenant $tenant, Category $category): Product
    {
        $subCategory = SubCategory::create([
            'tenant_id' => $tenant->id,
            'category_id' => $category->id,
            'name' => 'Sub '.$tenant->slug,
            'slug' => 'sub-'.$tenant->slug,
        ]);

        return Product::create([
            'tenant_id' => $tenant->id,
            'category_id' => $category->id,
            'sub_category_id' => $subCategory->id,
            'name' => 'Product '.$tenant->slug,
            'slug' => 'product-'.$tenant->slug,
            'price' => 10000,
            'stock' => 5,
        ]);
    }

    public function test_catalog_queries_return_empty_without_tenant_context(): void
    {
        $tenant = $this->createTenant('Tenant A', 'tenant-a');
        $category = $this->createCategory($tenant, 'Shoes');
        $this->createProduct($tenant, $category);

        app(TenantContext::class)->clear();

        $this->assertSame(0, Category::query()->count());
        $this->assertSame(0, Product::query()->count());
    }

    public function test_two_tenants_cannot_read_each_others_catalog(): void
    {
        $tenantA = $this->createTenant('Tenant A', 'tenant-a');
        $tenantB = $this->createTenant('Tenant B', 'tenant-b');

        $categoryA = $this->createCategory($tenantA, 'Shoes A');
        $categoryB = $this->createCategory($tenantB, 'Shoes B');

        $productA = $this->createProduct($tenantA, $categoryA);
        $productB = $this->createProduct($tenantB, $categoryB);

        $context = app(TenantContext::class);

        $context->set($tenantA);

        $this->assertSame(
            [$categoryA->id],
            Category::query()->pluck('id')->all()
        );

        $this->assertSame(
            [$productA->id],
            Product::query()->pluck('id')->all()
        );

        $context->set($tenantB);

        $this->assertSame(
            [$categoryB->id],
            Category::query()->pluck('id')->all()
        );

        $this->assertSame(
            [$productB->id],
            Product::query()->pluck('id')->all()
        );
    }

    public function test_product_cannot_use_another_tenants_category(): void
    {
        $tenantA = $this->createTenant('Tenant A', 'tenant-a');
        $tenantB = $this->createTenant('Tenant B', 'tenant-b');

        $categoryB = $this->createCategory($tenantB, 'Shoes B');

        $subCategoryB = SubCategory::create([
            'tenant_id' => $tenantB->id,
            'category_id' => $categoryB->id,
            'name' => 'Sub B',
            'slug' => 'sub-b',
        ]);

        $this->expectException(QueryException::class);

        Product::create([
            'tenant_id' => $tenantA->id,
            'category_id' => $categoryB->id,
            'sub_category_id' => $subCategoryB->id,
            'name' => 'Invalid Product',
            'slug' => 'invalid-product',
            'price' => 10000,
            'stock' => 1,
        ]);
    }

    public function test_verified_domain_resolves_correct_tenant_and_clears_context(): void
    {
        $tenant = $this->createTenant('Tenant A', 'tenant-a');

        $store = Store::create([
            'tenant_id' => $tenant->id,
            'name' => 'Store A',
            'slug' => 'store-a',
            'status' => 'active',
        ]);

        StoreDomain::create([
            'store_id' => $store->id,
            'domain' => 'shop-a.example.test',
            'verification_status' => 'verified',
        ]);

        $context = app(TenantContext::class);
        $middleware = app(ResolveTenantFromDomain::class);

        $request = Request::create('http://shop-a.example.test/');

        $response = $middleware->handle(
            $request,
            function () use ($context, $tenant) {
                $this->assertTrue(
                    $context->current()?->is($tenant) ?? false
                );

                return response('ok');
            }
        );

        $this->assertSame('ok', $response->getContent());
        $this->assertFalse($context->hasTenant());
    }

    public function test_unverified_domain_is_rejected_and_context_is_cleared(): void
    {
        $tenant = $this->createTenant('Tenant A', 'tenant-a');

        $store = Store::create([
            'tenant_id' => $tenant->id,
            'name' => 'Store A',
            'slug' => 'store-a',
            'status' => 'active',
        ]);

        StoreDomain::create([
            'store_id' => $store->id,
            'domain' => 'pending.example.test',
            'verification_status' => 'pending',
        ]);

        $context = app(TenantContext::class);
        $context->set($tenant);

        try {
            app(ResolveTenantFromDomain::class)->handle(
                Request::create('http://pending.example.test/'),
                fn () => response('should not be reached')
            );

            $this->fail('Unverified domain must be rejected.');
        } catch (NotFoundHttpException) {
            $this->assertFalse($context->hasTenant());
        }
    }
}
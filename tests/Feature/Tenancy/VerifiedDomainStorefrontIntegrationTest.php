<?php

namespace Tests\Feature\Tenancy;

use App\Models\Category;
use App\Models\Product;
use App\Models\Store;
use App\Models\StoreDomain;
use App\Models\SubCategory;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Tenancy\DomainVerificationService;
use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

class VerifiedDomainStorefrontIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private FakeStorefrontDomainVerificationService $verificationService;

    protected function setUp(): void
    {
        parent::setUp();

        config(['operational.mode' => 'multi_tenant']);

        $this->verificationService = new FakeStorefrontDomainVerificationService;

        $this->app->instance(
            DomainVerificationService::class,
            $this->verificationService
        );
    }

    private function createStore(string $name): Store
    {
        $owner = User::factory()->create();

        $tenant = Tenant::create([
            'name' => $name,
            'slug' => str($name)->slug().'-'.$owner->id,
            'status' => 'active',
            'created_by_user_id' => $owner->id,
        ]);

        return Store::create([
            'tenant_id' => $tenant->id,
            'name' => $name,
            'slug' => str($name)->slug().'-'.$owner->id,
            'status' => 'active',
        ]);
    }

    private function createProductForTenant(
        Tenant $tenant,
        string $name
    ): Product {
        return app(TenantContext::class)->run($tenant, function () use ($tenant, $name) {
            $category = Category::create([
                'tenant_id' => $tenant->id,
                'name' => 'Category '.$tenant->slug,
                'slug' => 'category-'.$tenant->slug,
            ]);

            $subCategory = SubCategory::create([
                'tenant_id' => $tenant->id,
                'category_id' => $category->id,
                'name' => 'Subcategory '.$tenant->slug,
                'slug' => 'subcategory-'.$tenant->slug,
            ]);

            return Product::create([
                'tenant_id' => $tenant->id,
                'category_id' => $category->id,
                'sub_category_id' => $subCategory->id,
                'name' => $name,
                'slug' => str($name)->slug(),
                'price' => 100000,
                'stock' => 10,
            ]);
        });
    }

    public function test_verified_domain_resolves_tenant_and_displays_its_storefront(): void
    {
        $storeA = $this->createStore('Store A');
        $storeB = $this->createStore('Store B');

        $tenantA = $storeA->tenant;
        $tenantB = $storeB->tenant;

        $productA = $this->createProductForTenant(
            $tenantA,
            'Product Belongs To Store A'
        );

        $productB = $this->createProductForTenant(
            $tenantB,
            'Product Belongs To Store B'
        );

        // Pastikan tidak ada tenant context sebelum request.
        $context = app(TenantContext::class);
        $context->clear();

        $this->assertFalse($context->hasTenant());

        // Daftarkan domain melalui service sebenarnya.
        $challenge = $this->verificationService->create(
            $storeA,
            'shop-a.example.test'
        );

        // Simulasikan TXT record DNS yang benar.
        $this->verificationService->records = [
            $challenge->record_value,
        ];

        // Verifikasi domain melalui service sebenarnya.
        $this->assertTrue(
            $this->verificationService->verify($challenge)
        );

        $domain = StoreDomain::findOrFail(
            $challenge->store_domain_id
        );

        $this->assertSame('verified', $domain->verification_status);
        $this->assertTrue($domain->is_primary);
        $this->assertNotNull($domain->verified_at);

        // Tangkap tenant context ketika storefront sedang dirender.
        $resolvedTenantId = null;

        View::composer('storefront.home', function () use (&$resolvedTenantId) {
            $resolvedTenantId = app(TenantContext::class)
                ->current()
                ?->getKey();
        });

        // Request menggunakan hostname domain yang sudah diverifikasi.
        $response = $this->get('http://shop-a.example.test/');

        $response->assertOk();
        $response->assertSee($productA->name);
        $response->assertDontSee($productB->name);

        // Pastikan tenant context aktif ketika controller merender view.
        $this->assertSame($tenantA->id, $resolvedTenantId);

        // Middleware harus membersihkan context setelah request selesai.
        $this->assertFalse($context->hasTenant());
        $this->assertNull($context->current());
    }
}

class FakeStorefrontDomainVerificationService extends DomainVerificationService
{
    public array $records = [];

    protected function lookupTxtRecords(string $hostname): array
    {
        return $this->records;
    }
}

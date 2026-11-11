<?php

namespace Tests\Feature\Tenancy;

use App\Models\Order;
use App\Models\Product;
use App\Models\Tenant;
use App\Models\TenantMembership;
use App\Models\User;
use App\Models\Category;
use App\Models\SubCategory;
use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IsolationSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['operational.mode' => 'multi_tenant']);
        app(TenantContext::class)->clear();
    }

    public function test_tenant_a_cannot_read_tenant_b_orders(): void
    {
        $tenantA = Tenant::factory()->create(['status' => 'active']);
        $tenantB = Tenant::factory()->create(['status' => 'active']);
        $user = User::factory()->create();

        TenantMembership::factory()->create([
            'tenant_id' => $tenantA->id,
            'user_id' => $user->id,
            'role' => TenantMembership::ROLE_OWNER,
            'status' => 'active',
        ]);
        
        $orderB = app(TenantContext::class)->run($tenantB, fn () => Order::factory()->create(['tenant_id' => $tenantB->id])
        );

        $this->actingAs($user);
        $result = Order::query()->find($orderB->id);
        $this->assertNull($result);
    }

    public function test_tenant_a_cannot_use_tenant_b_product(): void
    {
        $tenantA = Tenant::factory()->create(['status' => 'active']);
        $tenantB = Tenant::factory()->create(['status' => 'active']);

        [$categoryB, $subCategoryB, $productB] = app(TenantContext::class)->run(
            $tenantB,
            function () use ($tenantB) {
                $category = Category::factory()->create([
                    'tenant_id' => $tenantB->id,
                ]);

                $subCategory = SubCategory::factory()->create([
                    'category_id' => $category->id,
                    'tenant_id' => $tenantB->id,
                ]);

                $product = Product::factory()->create([
                    'tenant_id' => $tenantB->id,
                    'category_id' => $category->id,
                    'sub_category_id' => $subCategory->id,
                ]);

                return [$category, $subCategory, $product];
            }
        );
        
        $result = app(TenantContext::class)->run($tenantA, fn () => Product::query()->find($productB->id));
        $this->assertNull($result);
    }

    public function test_tenant_context_is_cleared_after_request(): void
    {
        $tenant = Tenant::factory()->create(['status' => 'active']);
        $context = app(TenantContext::class);
        
        $context->run($tenant, function () use ($context, $tenant): void {
            $this->assertSame($tenant->id, $context->current()->id);
            $this->assertTrue($context->hasTenant());
        });
        
        $this->assertNull($context->current());
        $this->assertFalse($context->hasTenant());
        $context->set($tenant);
    }
}
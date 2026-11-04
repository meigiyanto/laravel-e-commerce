<?php

namespace Tests\Feature;

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

class TenantIsolationSecurityTest extends TestCase
{
    use RefreshDatabase;

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

        $orderB = Order::factory()->create([
            'tenant_id' => $tenantB->id,
        ]);

        app(TenantContext::class)->set($tenantA);

        $this->actingAs($user);

        $result = Order::query()->find($orderB->id);

        $this->assertNull($result);
    }

    public function test_tenant_a_cannot_use_tenant_b_product(): void
    {
        $tenantA = Tenant::factory()->create(['status' => 'active']);
        $tenantB = Tenant::factory()->create(['status' => 'active']);

        $categoryB = Category::factory()->create([
            'tenant_id' => $tenantB->id,
        ]);

        $subCategoryB = SubCategory::factory()->create([
            'category_id' => $categoryB->id,
            'tenant_id' => $tenantB->id,
        ]);

        $productB = Product::factory()->create([
            'tenant_id' => $tenantB->id,
            'category_id' => $categoryB->id,
            'sub_category_id' => $subCategoryB->id,
        ]);

        app(TenantContext::class)->set($tenantA);

        $result = Product::query()->find($productB->id);

        $this->assertNull($result);
    }

    public function test_tenant_context_is_cleared_after_request(): void
    {
        $tenant = Tenant::factory()->create(['status' => 'active']);

        $context = app(TenantContext::class);
        $context->set($tenant);

        $this->assertSame($tenant->id, $context->current()->id);

        $context->clear();

        $this->assertNull($context->current());
    }
}
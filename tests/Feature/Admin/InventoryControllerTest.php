<?php

namespace Tests\Feature\Admin;

use App\Models\Product;
use App\Models\Category;
use App\Models\SubCategory;
use App\Models\User;
use App\Models\Inventory;
use App\Models\ProductVariant;
use App\Models\Tenant;
use App\Models\Store;
use App\Models\StoreDomain;
use App\Models\TenantMembership;
use App\Support\Tenancy\TenantContext;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;


class InventoryControllerTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        config(['operational.mode' => 'multi_tenant']);
        $tenant = Tenant::factory()->create();
        app(TenantContext::class)->set($tenant);
    }

    private function createTenantDomain(Product $product): string
    {
        $tenant = Tenant::query()->findOrFail($product->tenant_id);

        $store = Store::firstOrCreate(
            ['tenant_id' => $tenant->id],
            [
                'name' => $tenant->name,
                'slug' => $tenant->slug,
                'status' => 'active',
            ]
        );

        $domain = 'checkout-test-'.$tenant->id.'.example.test';

        StoreDomain::updateOrCreate(
            ['domain' => $domain],
            [
                'store_id' => $store->id,
                'verification_status' => 'verified',
                'is_primary' => true,
            ]
        );

        return $domain;
    }

    private function activateTenant(Product $product): void
    {
        $tenant = Tenant::query()->findOrFail($product->tenant_id);

        app(TenantContext::class)->set($tenant);
    }

    private function tenantUrl(
        Product $product,
        string $routeName,
        mixed $parameters = []
    ): string {
        $domain = $this->createTenantDomain($product);

        return 'http://'.$domain.route($routeName, $parameters, false);
    }

    private function createTenantAdmin(Product $product): User
    {
        $tenant = Tenant::query()->findOrFail($product->tenant_id);

        $admin = User::factory()->create(['role' => 'admin']);

        TenantMembership::create([
            'tenant_id' => $tenant->id,
            'user_id' => $admin->id,
            'role' => TenantMembership::ROLE_ADMIN,
            'status' => 'active',
            'accepted_at' => now(),
        ]);

        return $admin;
    }

    public function test_guest_cannot_access_inventory(): void
    {
        $product = Product::factory()->create(['stock' => 10]);
        $response = $this->get($this->tenantUrl($product, 'admin.inventory.index'));
        $response->assertRedirect($this->tenantUrl($product, 'admin.login'));
    }

    public function test_non_admin_cannot_access_inventory(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $product = Product::factory()->create(['stock' => 10]);
        $response = $this
        ->actingAs($user)
        ->get($this->tenantUrl($product, 'admin.inventory.index'));
        $this->activateTenant($product);

        $response->assertForbidden();
    }

    public function test_admin_can_view_inventory(): void
    {
        $product = Product::factory()->create(['stock' => 10]);
        $admin = $this->createTenantAdmin($product);
        $response = $this->actingAs($admin)->get($this->tenantUrl($product, 'admin.inventory.index'));
        $this->activateTenant($product);
        $response->assertOk()->assertViewIs('admin.inventory.index')->assertSee($product->name);
    }

    public function test_admin_can_update_product_stock(): void
    {
        $product = Product::factory()->create(['stock' => 10]);
        $admin = $this->createTenantAdmin($product);

        $response = $this
        ->actingAs($admin)
        ->patch($this->tenantUrl($product, 'admin.inventory.update-stock', $product), [
            'stock' => 25,
        ]);
        $this->activateTenant($product);

        $response->assertRedirect($this->tenantUrl($product, 'admin.inventory.index'))
        ->assertSessionHas('success');

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'stock' => 25,
        ]);
    }

    public function test_admin_cannot_set_negative_stock(): void
    {
        $product = Product::factory()->create(['stock' => 10]);
        $admin = $this->createTenantAdmin($product);

        $response = $this->actingAs($admin)
        ->patch($this->tenantUrl($product, 'admin.inventory.update-stock', $product), [
            'stock' => -1,
        ]);
        $this->activateTenant($product);

        $response->assertSessionHasErrors('stock');

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'stock' => 10,
        ]);
    }

    public function test_admin_can_add_stock(): void
    {
        $product = Product::factory()->create(['stock' => 10]);
        $admin = $this->createTenantAdmin($product);

        $response = $this->actingAs($admin)
        ->post($this->tenantUrl($product, 'admin.inventory.adjust-stock', $product), [
            'quantity' => 5,
            'type' => 'add',
        ]);
        $this->activateTenant($product);

        $response->assertRedirect($this->tenantUrl($product, 'admin.inventory.index'))
        ->assertSessionHas('success');

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'stock' => 15,
        ]);
    }

    public function test_admin_can_subtract_stock(): void
    {
        $product = Product::factory()->create(['stock' => 10]);
        $admin = $this->createTenantAdmin($product);

        $response = $this
        ->actingAs($admin)
        ->post($this->tenantUrl($product, 'admin.inventory.adjust-stock', $product), [
            'quantity' => 4,
            'type' => 'subtract',
        ]);
        $this->activateTenant($product);

        $response
        ->assertRedirect($this->tenantUrl($product, 'admin.inventory.index'))
        ->assertSessionHas('success');

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'stock' => 6,
        ]);
    }

    public function test_admin_cannot_subtract_more_stock_than_available(): void
    {
        $product = Product::factory()->create(['stock' => 10]);
        $admin = $this->createTenantAdmin($product);

        $response = $this
        ->actingAs($admin)
        ->post($this->tenantUrl($product, 'admin.inventory.adjust-stock', $product), [
            'quantity' => 11,
            'type' => 'subtract',
        ]);
        $this->activateTenant($product);

        $response
        ->assertRedirect()
        ->assertSessionHasErrors('quantity');

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'stock' => 10,
        ]);
    }

    public function test_admin_cannot_adjust_stock_by_zero(): void
    {
        $product = Product::factory()->create(['stock' => 10]);
        $admin = $this->createTenantAdmin($product);

        $response = $this
        ->actingAs($admin)
        ->post($this->tenantUrl($product, 'admin.inventory.adjust-stock', $product), [
            'quantity' => 0,
            'type' => 'add',
        ]);
        $this->activateTenant($product);

        $response
        ->assertSessionHasErrors('quantity');

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'stock' => 10,
        ]);
    }

    public function test_non_admin_cannot_adjust_product_stock(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $product = Product::factory()->create(['stock' => 10]);

        $response = $this
        ->actingAs($user)
        ->post($this->tenantUrl($product, 'admin.inventory.adjust-stock', $product), [
            'quantity' => 5,
            'type' => 'add',
        ]);
        $this->activateTenant($product);

        $response->assertForbidden();

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'stock' => 10,
        ]);
    }

    public function test_updating_one_variant_keeps_product_stock_equal_to_all_variant_stock(): void
    {
        $product = Product::factory()->create(['stock' => 20]);
        $admin = $this->createTenantAdmin($product);

        $variantA = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'INV-A-' . uniqid(),
            'name' => 'Varian A',
            'price' => 100000,
            'is_default' => true,
            'status' => 'active',
        ]);

        $variantB = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'INV-B-' . uniqid(),
            'name' => 'Varian B',
            'price' => 120000,
            'is_default' => false,
            'status' => 'active',
        ]);

        $inventoryA = Inventory::create([
            'product_variant_id' => $variantA->id,
            'quantity' => 8,
            'reserved_quantity' => 0,
            'reorder_level' => 0,
        ]);

        $inventoryB = Inventory::create([
            'product_variant_id' => $variantB->id,
            'quantity' => 12,
            'reserved_quantity' => 0,
            'reorder_level' => 0,
        ]);

        $response = $this
        ->actingAs($admin)
        ->patch($this->tenantUrl($product, 'admin.inventory.update-stock', $product), [
            'stock' => 5,
        ]);
        $this->activateTenant($product);
        $response
        ->assertRedirect($this->tenantUrl($product, 'admin.inventory.index'))
        ->assertSessionHas('success');

        $this->assertSame(5, (int) $inventoryA->fresh()->quantity);
        $this->assertSame(12, (int) $inventoryB->fresh()->quantity);
        $this->assertSame(17, (int) $product->fresh()->stock);
    }
}
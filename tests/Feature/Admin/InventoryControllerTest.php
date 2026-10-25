<?php

namespace Tests\Feature\Admin;

use App\Models\Product;
use App\Models\User;
use App\Models\Inventory;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;


class InventoryControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_inventory(): void
    {
        $response = $this->get(route('admin.inventory.index'));

        $response->assertRedirect(route('admin.login'));
    }

    public function test_non_admin_cannot_access_inventory(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $response = $this
            ->actingAs($user)
            ->get(route('admin.inventory.index'));

        $response->assertForbidden();
    }

    public function test_admin_can_view_inventory(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $product = Product::factory()->create(['stock' => 10]);

        $response = $this
            ->actingAs($admin)
            ->get(route('admin.inventory.index'));

        $response
            ->assertOk()
            ->assertViewIs('admin.inventory.index')
            ->assertSee($product->name);
    }

    public function test_admin_can_update_product_stock(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $product = Product::factory()->create(['stock' => 10]);

        $response = $this
            ->actingAs($admin)
            ->patch(route('admin.inventory.update-stock', $product), [
                'stock' => 25,
            ]);

        $response
            ->assertRedirect(route('admin.inventory.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'stock' => 25,
        ]);
    }

    public function test_admin_cannot_set_negative_stock(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $product = Product::factory()->create(['stock' => 10]);

        $response = $this
            ->actingAs($admin)
            ->patch(route('admin.inventory.update-stock', $product), [
                'stock' => -1,
            ]);

        $response
            ->assertSessionHasErrors('stock');

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'stock' => 10,
        ]);
    }

    public function test_admin_can_add_stock(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $product = Product::factory()->create(['stock' => 10]);

        $response = $this
            ->actingAs($admin)
            ->post(route('admin.inventory.adjust-stock', $product), [
                'quantity' => 5,
                'type' => 'add',
            ]);

        $response
            ->assertRedirect(route('admin.inventory.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'stock' => 15,
        ]);
    }

    public function test_admin_can_subtract_stock(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $product = Product::factory()->create(['stock' => 10]);

        $response = $this
            ->actingAs($admin)
            ->post(route('admin.inventory.adjust-stock', $product), [
                'quantity' => 4,
                'type' => 'subtract',
            ]);

        $response
            ->assertRedirect(route('admin.inventory.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'stock' => 6,
        ]);
    }

    public function test_admin_cannot_subtract_more_stock_than_available(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $product = Product::factory()->create(['stock' => 10]);

        $response = $this
            ->actingAs($admin)
            ->post(route('admin.inventory.adjust-stock', $product), [
                'quantity' => 11,
                'type' => 'subtract',
            ]);

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
        $admin = User::factory()->create(['role' => 'admin']);
        $product = Product::factory()->create(['stock' => 10]);

        $response = $this
            ->actingAs($admin)
            ->post(route('admin.inventory.adjust-stock', $product), [
                'quantity' => 0,
                'type' => 'add',
            ]);

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
            ->post(route('admin.inventory.adjust-stock', $product), [
                'quantity' => 5,
                'type' => 'add',
            ]);

        $response->assertForbidden();

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'stock' => 10,
        ]);
    }
    
    public function test_updating_one_variant_keeps_product_stock_equal_to_all_variant_stock(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
    
        $product = Product::factory()->create(['stock' => 20]);
    
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
            ->patch(route('admin.inventory.update-stock', $product), [
                'stock' => 5,
            ]);
    
        $response
            ->assertRedirect(route('admin.inventory.index'))
            ->assertSessionHas('success');
    
        $this->assertSame(5, (int) $inventoryA->fresh()->quantity);
        $this->assertSame(12, (int) $inventoryB->fresh()->quantity);
        $this->assertSame(17, (int) $product->fresh()->stock);
    }
}

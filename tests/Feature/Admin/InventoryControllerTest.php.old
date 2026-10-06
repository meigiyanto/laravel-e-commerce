<?php

namespace Tests\Feature\Admin;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_inventory(): void
    {
        $response = $this->get(route('admin.inventory.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_non_admin_cannot_access_inventory(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $response = $this->actingAs($user)->get(route('admin.inventory.index'));

        $response->assertForbidden();
    }

    public function test_admin_can_view_inventory(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $product = Product::factory()->create(['stock' => 10]);

        $response = $this->actingAs($admin)->get(route('admin.inventory.index'));

        $response->assertOk()
            ->assertViewIs('admin.inventory.index')
            ->assertSee($product->name);
    }

    public function test_admin_can_update_product_stock(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $product = Product::factory()->create(['stock' => 10]);

        $response = $this->actingAs($admin)->patch(
            route('admin.inventory.update-stock', $product),
            ['stock' => 25]
        );

        $response->assertRedirect(route('admin.inventory.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'stock' => 25,
        ]);
    }

    public function test_admin_can_add_stock(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $product = Product::factory()->create(['stock' => 10]);

        $response = $this->actingAs($admin)->post(
            route('admin.inventory.adjust-stock', $product),
            [
                'type' => 'add',
                'quantity' => 5,
            ]
        );

        $response->assertRedirect(route('admin.inventory.index'))
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

        $response = $this->actingAs($admin)->post(
            route('admin.inventory.adjust-stock', $product),
            [
                'type' => 'subtract',
                'quantity' => 4,
            ]
        );

        $response->assertRedirect(route('admin.inventory.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'stock' => 6,
        ]);
    }

    public function test_admin_cannot_subtract_more_stock_than_available(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $product = Product::factory()->create(['stock' => 5]);

        $response = $this->actingAs($admin)->post(
            route('admin.inventory.adjust-stock', $product),
            [
                'type' => 'subtract',
                'quantity' => 6,
            ]
        );

        $response->assertRedirect()
            ->assertSessionHas('error');

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'stock' => 5,
        ]);
    }

    public function test_adjust_stock_rejects_zero_quantity(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $product = Product::factory()->create(['stock' => 10]);

        $response = $this->actingAs($admin)->post(
            route('admin.inventory.adjust-stock', $product),
            [
                'type' => 'add',
                'quantity' => 0,
            ]
        );

        $response->assertSessionHasErrors('quantity');

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'stock' => 10,
        ]);
    }

    public function test_non_admin_cannot_adjust_stock(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $product = Product::factory()->create(['stock' => 10]);

        $response = $this->actingAs($user)->post(
            route('admin.inventory.adjust-stock', $product),
            [
                'type' => 'add',
                'quantity' => 5,
            ]
        );

        $response->assertForbidden();

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'stock' => 10,
        ]);
    }
}

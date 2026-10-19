<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\User;
use App\Models\Inventory;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GuestCartLoginMergeTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cart_is_merged_into_user_cart_after_login(): void
    {
        $user = User::factory()->create();

        $product = Product::factory()->create([
            'stock' => 10,
        ]);

        $response = $this
            ->withSession([
                'cart' => [
                    (string) $product->id => 2,
                ],
            ])
            ->post('/login', [
                'email' => $user->email,
                'password' => 'password',
            ]);

        $this->assertAuthenticatedAs($user);

        $response->assertRedirect(
            route('account.index', absolute: false)
        );

        $cart = Cart::where('user_id', $user->id)->first();

        $this->assertNotNull($cart);

        $this->assertDatabaseHas('cart_items', [
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        $response->assertSessionMissing('cart');
    }

    public function test_guest_cart_quantity_is_merged_with_existing_user_cart_quantity(): void
    {
        $user = User::factory()->create();

        $product = Product::factory()->create([
            'stock' => 10,
        ]);

        $cart = Cart::create([
            'user_id' => $user->id,
        ]);

        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 3,
        ]);

        $response = $this
            ->withSession([
                'cart' => [
                    (string) $product->id => 2,
                ],
            ])
            ->post('/login', [
                'email' => $user->email,
                'password' => 'password',
            ]);

        $this->assertAuthenticatedAs($user);

        $response->assertRedirect(
            route('account.index', absolute: false)
        );

        $this->assertDatabaseHas('cart_items', [
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 5,
        ]);

        $response->assertSessionMissing('cart');
    }

    public function test_guest_cart_quantity_does_not_exceed_product_stock_during_merge(): void
    {
        $user = User::factory()->create();

        $product = Product::factory()->create([
            'stock' => 10,
        ]);

        $cart = Cart::create([
            'user_id' => $user->id,
        ]);

        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 6,
        ]);

        $response = $this
            ->withSession([
                'cart' => [
                    (string) $product->id => 7,
                ],
            ])
            ->post('/login', [
                'email' => $user->email,
                'password' => 'password',
            ]);

        $this->assertAuthenticatedAs($user);

        $response->assertRedirect(
            route('account.index', absolute: false)
        );

        $this->assertDatabaseHas('cart_items', [
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 10,
        ]);

        $response->assertSessionMissing('cart');
    }

    public function test_login_without_guest_cart_still_works_normally(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($user);

        $response->assertRedirect(
            route('account.index', absolute: false)
        );

        $this->assertDatabaseMissing('carts', [
            'user_id' => $user->id,
        ]);
    }
    
    public function test_new_guest_cart_format_merges_distinct_variants_after_login(): void
    {
        $user = User::factory()->create();
    
        $product = Product::factory()->create([
            'price' => 10000,
            'stock' => 10,
        ]);
    
        $small = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'MERGE-SMALL-'.$product->id,
            'name' => 'Small',
            'price' => '10000.00',
            'is_default' => true,
            'status' => 'active',
        ]);
    
        $large = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'MERGE-LARGE-'.$product->id,
            'name' => 'Large',
            'price' => '15000.00',
            'is_default' => false,
            'status' => 'active',
        ]);
    
        foreach ([$small, $large] as $variant) {
            Inventory::create([
                'product_variant_id' => $variant->id,
                'quantity' => 10,
                'reserved_quantity' => 0,
                'reorder_level' => 0,
            ]);
        }
    
        $response = $this
            ->withSession([
                'cart' => [
                    'variant_'.$small->id => [
                        'product_id' => $product->id,
                        'product_variant_id' => $small->id,
                        'quantity' => 2,
                    ],
                    'variant_'.$large->id => [
                        'product_id' => $product->id,
                        'product_variant_id' => $large->id,
                        'quantity' => 3,
                    ],
                ],
            ])
            ->post('/login', [
                'email' => $user->email,
                'password' => 'password',
            ]);
    
        $this->assertAuthenticatedAs($user);
    
        $response->assertRedirect(
            route('account.index', absolute: false)
        );
    
        $cart = Cart::where('user_id', $user->id)->firstOrFail();
    
        $this->assertDatabaseHas('cart_items', [
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'product_variant_id' => $small->id,
            'quantity' => 2,
        ]);
    
        $this->assertDatabaseHas('cart_items', [
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'product_variant_id' => $large->id,
            'quantity' => 3,
        ]);
    
        $this->assertSame(2, $cart->items()->count());
    
        $response->assertSessionMissing('cart');
    }
    
    public function test_new_guest_cart_format_merges_quantity_into_matching_user_variant(): void
    {
        $user = User::factory()->create();
    
        $product = Product::factory()->create([
            'price' => 10000,
            'stock' => 10,
        ]);
    
        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'MERGE-EXISTING-'.$product->id,
            'name' => 'Default',
            'price' => '12000.00',
            'is_default' => true,
            'status' => 'active',
        ]);
    
        Inventory::create([
            'product_variant_id' => $variant->id,
            'quantity' => 10,
            'reserved_quantity' => 0,
            'reorder_level' => 0,
        ]);
    
        $cart = Cart::create([
            'user_id' => $user->id,
        ]);
    
        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'quantity' => 2,
        ]);
    
        $response = $this
            ->withSession([
                'cart' => [
                    'variant_'.$variant->id => [
                        'product_id' => $product->id,
                        'product_variant_id' => $variant->id,
                        'quantity' => 3,
                    ],
                ],
            ])
            ->post('/login', [
                'email' => $user->email,
                'password' => 'password',
            ]);
    
        $this->assertAuthenticatedAs($user);
    
        $response->assertRedirect(
            route('account.index', absolute: false)
        );
    
        $this->assertDatabaseHas('cart_items', [
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'quantity' => 5,
        ]);
    
        $this->assertSame(
            1,
            CartItem::where('cart_id', $cart->id)
                ->where('product_variant_id', $variant->id)
                ->count(),
        );
    
        $response->assertSessionMissing('cart');
    }
}

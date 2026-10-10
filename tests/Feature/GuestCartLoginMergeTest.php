<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\User;
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
}

<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartControllerIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private function createVariant(
        Product $product,
        string $name,
        string $price,
        int $stock = 10,
        bool $default = false,
    ): ProductVariant {
        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'INTEGRATION-'.$product->id.'-'.uniqid(),
            'name' => $name,
            'price' => $price,
            'is_default' => $default,
            'status' => 'active',
        ]);

        Inventory::create([
            'product_variant_id' => $variant->id,
            'quantity' => $stock,
            'reserved_quantity' => 0,
            'reorder_level' => 0,
        ]);

        return $variant;
    }

    private function putGuestVariantInCart(
        Product $product,
        ProductVariant $variant,
        int $quantity,
    ): void {
        $cart = session('cart', []);

        $cart['variant_'.$variant->id] = [
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'quantity' => $quantity,
        ];

        session()->put('cart', $cart);
    }

    public function test_guest_cart_page_renders_multiple_variants_of_same_product(): void
    {
        $product = Product::factory()->create([
            'name' => 'Integration Shirt',
            'price' => 10000,
            'stock' => 10,
        ]);

        $small = $this->createVariant(
            $product,
            'Small',
            '12000.00',
            10,
            true,
        );

        $large = $this->createVariant(
            $product,
            'Large',
            '18000.00',
            10,
        );

        $this->withSession([
            'cart' => [
                'variant_'.$small->id => [
                    'product_id' => $product->id,
                    'product_variant_id' => $small->id,
                    'quantity' => 2,
                ],
                'variant_'.$large->id => [
                    'product_id' => $product->id,
                    'product_variant_id' => $large->id,
                    'quantity' => 1,
                ],
            ],
        ]);

        $response = $this->get(route('cart.index'));

        $response->assertOk();
        $response->assertViewIs('storefront.cart');
        $response->assertSee('Small');
        $response->assertSee('Large');
        $response->assertSee('Rp 12.000');
        $response->assertSee('Rp 18.000');
        $response->assertSee('Rp 42.000');
    }

    public function test_guest_drawer_renders_variant_price_and_line_total(): void
    {
        $product = Product::factory()->create([
            'name' => 'Drawer Shirt',
            'price' => 10000,
            'stock' => 10,
        ]);

        $variant = $this->createVariant(
            $product,
            'Premium',
            '17500.00',
            10,
            true,
        );

        $this->withSession([
            'cart' => [
                'variant_'.$variant->id => [
                    'product_id' => $product->id,
                    'product_variant_id' => $variant->id,
                    'quantity' => 2,
                ],
            ],
        ]);

        $response = $this->getJson(route('cart.drawer'));

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('cart_count', 2);

        $html = $response->json('html');

        $this->assertIsString($html);
        $this->assertStringContainsString('Drawer Shirt', $html);
        $this->assertStringContainsString('Premium', $html);
        $this->assertStringContainsString('Rp 17.500', $html);
        $this->assertStringContainsString('Rp 35.000', $html);
    }

    public function test_authenticated_drawer_renders_database_cart_items(): void
    {
        $user = User::factory()->create();

        $product = Product::factory()->create([
            'name' => 'Member Cart Shirt',
            'price' => 10000,
            'stock' => 10,
        ]);

        $variant = $this->createVariant(
            $product,
            'Member Premium',
            '20000.00',
            10,
            true,
        );

        $cart = Cart::create([
            'user_id' => $user->id,
        ]);

        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'quantity' => 2,
        ]);

        $response = $this->actingAs($user)
            ->getJson(route('cart.drawer'));

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('cart_count', 2);

        $html = $response->json('html');

        $this->assertStringContainsString('Member Cart Shirt', $html);
        $this->assertStringContainsString('Member Premium', $html);
        $this->assertStringContainsString('Rp 20.000', $html);
        $this->assertStringContainsString('Rp 40.000', $html);
    }

    public function test_guest_can_update_variant_quantity_through_controller(): void
    {
        $product = Product::factory()->create([
            'price' => 10000,
            'stock' => 10,
        ]);

        $variant = $this->createVariant(
            $product,
            'Blue',
            '12000.00',
            10,
            true,
        );

        $this->withSession([
            'cart' => [
                'variant_'.$variant->id => [
                    'product_id' => $product->id,
                    'product_variant_id' => $variant->id,
                    'quantity' => 1,
                ],
            ],
        ]);

        $response = $this->patchJson(
            route('cart.product.update', $variant->id),
            ['quantity' => 4],
        );

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('cart_count', 4);
        $response->assertJsonPath('total', 48000);

        $this->assertSame(
            4,
            session("cart.variant_{$variant->id}.quantity"),
        );
    }

    public function test_guest_can_remove_variant_through_controller(): void
    {
        $product = Product::factory()->create([
            'stock' => 10,
        ]);

        $first = $this->createVariant(
            $product,
            'Red',
            '12000.00',
            10,
            true,
        );

        $second = $this->createVariant(
            $product,
            'Green',
            '15000.00',
            10,
        );

        $this->withSession([
            'cart' => [
                'variant_'.$first->id => [
                    'product_id' => $product->id,
                    'product_variant_id' => $first->id,
                    'quantity' => 1,
                ],
                'variant_'.$second->id => [
                    'product_id' => $product->id,
                    'product_variant_id' => $second->id,
                    'quantity' => 2,
                ],
            ],
        ]);

        $response = $this->deleteJson(
            route('cart.product.destroy', $first->id),
        );

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('cart_count', 2);

        $this->assertArrayNotHasKey(
            'variant_'.$first->id,
            session('cart'),
        );

        $this->assertArrayHasKey(
            'variant_'.$second->id,
            session('cart'),
        );
    }

    public function test_authenticated_user_can_update_and_remove_owned_cart_item(): void
    {
        $user = User::factory()->create();

        $product = Product::factory()->create([
            'price' => 10000,
            'stock' => 10,
        ]);

        $variant = $this->createVariant(
            $product,
            'Authenticated Premium',
            '17500.00',
            10,
            true,
        );

        $cart = Cart::create([
            'user_id' => $user->id,
        ]);

        $item = CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'quantity' => 2,
        ]);

        $this->actingAs($user);

        $updateResponse = $this->patchJson(
            route('cart.items.update', $item->id),
            ['quantity' => 3],
        );

        $updateResponse->assertOk();
        $updateResponse->assertJsonPath('success', true);
        $updateResponse->assertJsonPath('quantity', 3);
        $updateResponse->assertJsonPath('subtotal', 52500);

        $this->assertDatabaseHas('cart_items', [
            'id' => $item->id,
            'cart_id' => $cart->id,
            'quantity' => 3,
        ]);

        $deleteResponse = $this->deleteJson(
            route('cart.items.destroy', $item->id),
        );

        $deleteResponse->assertOk();
        $deleteResponse->assertJsonPath('success', true);

        $this->assertDatabaseMissing('cart_items', [
            'id' => $item->id,
        ]);
    }

    public function test_authenticated_user_cannot_update_another_users_cart_item(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $product = Product::factory()->create([
            'stock' => 10,
        ]);

        $variant = $this->createVariant(
            $product,
            'Private Variant',
            '15000.00',
            10,
            true,
        );

        $cart = Cart::create([
            'user_id' => $owner->id,
        ]);

        $item = CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'quantity' => 2,
        ]);

        $response = $this->actingAs($otherUser)->patchJson(
            route('cart.items.update', $item->id),
            ['quantity' => 4],
        );

        $response->assertNotFound();

        $this->assertDatabaseHas('cart_items', [
            'id' => $item->id,
            'cart_id' => $cart->id,
            'quantity' => 2,
        ]);
    }
}
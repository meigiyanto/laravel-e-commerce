<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Services\CartService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartServiceConsistencyTest extends TestCase
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
            'sku' => 'TEST-'.$product->id.'-'.uniqid(),
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

    public function test_guest_subtotal_uses_variant_price(): void
    {
        $product = Product::factory()->create([
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

        session()->put('cart', [
            'variant_'.$variant->id => [
                'product_id' => $product->id,
                'product_variant_id' => $variant->id,
                'quantity' => 2,
            ],
        ]);

        $this->assertSame(
            35000.0,
            app(CartService::class)->total(),
        );
    }

    public function test_same_product_can_have_two_separate_guest_cart_variants(): void
    {
        $product = Product::factory()->create([
            'price' => 10000,
            'stock' => 10,
        ]);

        $small = $this->createVariant(
            $product,
            'Small',
            '10000.00',
            10,
            true,
        );

        $large = $this->createVariant(
            $product,
            'Large',
            '15000.00',
            10,
        );

        $service = app(CartService::class);

        $service->add($product, 2, $small->id);
        $service->add($product, 3, $large->id);

        $cart = session('cart');

        $this->assertArrayHasKey('variant_'.$small->id, $cart);
        $this->assertArrayHasKey('variant_'.$large->id, $cart);
        $this->assertSame(2, $cart['variant_'.$small->id]['quantity']);
        $this->assertSame(3, $cart['variant_'.$large->id]['quantity']);
        $this->assertSame(5, $service->count());
        $this->assertSame(65000.0, $service->total());
    }

    public function test_guest_cart_quantity_can_be_updated_by_variant_id(): void
    {
        $product = Product::factory()->create([
            'stock' => 10,
        ]);

        $variant = $this->createVariant(
            $product,
            'Blue',
            '12000.00',
            10,
            true,
        );

        session()->put('cart', [
            'variant_'.$variant->id => [
                'product_id' => $product->id,
                'product_variant_id' => $variant->id,
                'quantity' => 1,
            ],
        ]);

        app(CartService::class)->update($variant->id, 4);

        $this->assertSame(
            4,
            session("cart.variant_{$variant->id}.quantity"),
        );
    }

    public function test_guest_cart_item_can_be_removed_by_variant_id(): void
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
            '13000.00',
            10,
        );

        session()->put('cart', [
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
        ]);

        app(CartService::class)->remove($first->id);

        $this->assertArrayNotHasKey(
            'variant_'.$first->id,
            session('cart'),
        );

        $this->assertArrayHasKey(
            'variant_'.$second->id,
            session('cart'),
        );
    }

    public function test_legacy_guest_session_is_migrated_to_variant_format(): void
    {
        $product = Product::factory()->create([
            'stock' => 10,
        ]);

        $variant = $this->createVariant(
            $product,
            'Default',
            (string) $product->price,
            10,
            true,
        );

        session()->put('cart', [
            (string) $product->id => 3,
        ]);

        $cart = app(CartService::class)->getGuestCart();

        $this->assertArrayHasKey('variant_'.$variant->id, $cart);
        $this->assertSame(
            $product->id,
            $cart['variant_'.$variant->id]['product_id'],
        );
        $this->assertSame(
            $variant->id,
            $cart['variant_'.$variant->id]['product_variant_id'],
        );
        $this->assertSame(
            3,
            $cart['variant_'.$variant->id]['quantity'],
        );
    }

    public function test_authenticated_cart_subtotal_uses_variant_price(): void
    {
        $user = User::factory()->create();

        $product = Product::factory()->create([
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

        $cart = Cart::create([
            'user_id' => $user->id,
        ]);

        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'quantity' => 2,
        ]);

        $this->actingAs($user);

        $this->assertSame(
            35000.0,
            app(CartService::class)->total(),
        );
    }
}

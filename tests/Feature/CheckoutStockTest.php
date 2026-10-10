<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckoutStockTest extends TestCase
{
    use RefreshDatabase;

    public function test_checkout_with_insufficient_stock_is_rejected_without_changing_data(): void
    {
        $user = User::factory()->create();

        $product = Product::factory()->create([
            'stock' => 1,
        ]);

        $cart = Cart::create([
            'user_id' => $user->id,
        ]);

        $cartItem = CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        $ordersBefore = Order::count();
        $paymentsBefore = Payment::count();
        $stockBefore = $product->fresh()->stock;

        $response = $this->actingAs($user)
            ->from(route('checkout.index'))
            ->post(route('checkout.store'), [
                'customer_name' => 'Test Customer',
                'phone' => '08123456789',
                'shipping_address' => 'Alamat pengujian',
                'notes' => '',
                'payment_method' => 'cod',
            ]);

        $response->assertRedirect(route('checkout.index'));
        $response->assertSessionHasErrors('stock');

        $this->assertSame($ordersBefore, Order::count());
        $this->assertSame($paymentsBefore, Payment::count());

        $this->assertSame(
            $stockBefore,
            $product->fresh()->stock
        );

        $this->assertDatabaseHas('cart_items', [
            'id' => $cartItem->id,
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 2,
        ]);
    }
}

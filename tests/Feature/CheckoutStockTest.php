<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use App\Models\Inventory;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\InventoryReservation;
use App\Services\VariantInventoryService;
use Illuminate\Validation\ValidationException;

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
    
    public function test_checkout_uses_selected_non_default_variant_and_creates_payment(): void
    {
        $user = User::factory()->create();
    
        $product = Product::factory()->create([
            'price' => 10000,
            'stock' => 15,
        ]);
    
        $defaultVariant = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'TEST-DEFAULT-'.uniqid(),
            'name' => 'Default',
            'price' => 10000,
            'is_default' => true,
            'status' => 'active',
        ]);
    
        $selectedVariant = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'TEST-SELECTED-'.uniqid(),
            'name' => 'Premium',
            'price' => 25000,
            'is_default' => false,
            'status' => 'active',
        ]);
    
        $defaultInventory = Inventory::create([
            'product_variant_id' => $defaultVariant->id,
            'quantity' => 10,
            'reserved_quantity' => 0,
            'reorder_level' => 0,
        ]);
    
        $selectedInventory = Inventory::create([
            'product_variant_id' => $selectedVariant->id,
            'quantity' => 5,
            'reserved_quantity' => 0,
            'reorder_level' => 0,
        ]);
        
        $cart = Cart::create([
            'user_id' => $user->id,
        ]);
    
        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'product_variant_id' => $selectedVariant->id,
            'quantity' => 2,
        ]);
    
        $response = $this->actingAs($user)
            ->post(route('checkout.store'), [
                'customer_name' => 'Test Customer',
                'phone' => '08123456789',
                'shipping_address' => 'Alamat pengujian',
                'notes' => '',
                'payment_method' => 'cod',
            ]);
    
        $order = Order::where('user_id', $user->id)
            ->latest('id')
            ->first();
    
        $this->assertNotNull($order);
    
        $response->assertRedirect(route('orders.show', $order));
    
        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_variant_id' => $selectedVariant->id,
            'price' => 25000,
            'quantity' => 2,
            'subtotal' => 50000,
        ]);
    
        $this->assertEquals(50000, (float) $order->subtotal);
        $this->assertEquals(50000, (float) $order->total);
    
        $this->assertSame(10, (int) $defaultInventory->fresh()->quantity);
        $this->assertSame(3, (int) $selectedInventory->fresh()->quantity);
    
        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'provider' => 'cod',
            'payment_method' => 'cod',
            'payment_type' => 'cash_on_delivery',
            'status' => 'pending',
            'gross_amount' => 50000,
        ]);
    
        $this->assertDatabaseMissing('cart_items', [
            'cart_id' => $cart->id,
        ]);
        
        $reservation = InventoryReservation::query()
            ->where('reference_type', Order::class)
            ->where('reference_id', $order->id)
            ->sole();
        
        $this->assertSame(
            InventoryReservation::STATUS_COMMITTED,
            $reservation->status
        );
        
        $this->assertSame($selectedVariant->id, $reservation->product_variant_id);
        $this->assertSame(2, (int) $reservation->quantity);
        $this->assertSame(3, (int) $selectedInventory->fresh()->quantity);
        $this->assertSame(0, (int) $selectedInventory->fresh()->reserved_quantity);
    }
    
    public function test_checkout_deducts_total_quantity_for_selected_variant(): void
    {
        $user = User::factory()->create();
    
        $product = Product::factory()->create([
            'price' => 20000,
            'stock' => 10,
        ]);
    
        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'TEST-DUPLICATE-'.uniqid(),
            'name' => 'Varian A',
            'price' => 20000,
            'is_default' => true,
            'status' => 'active',
        ]);
    
        $inventory = Inventory::create([
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
            'quantity' => 5,
        ]);
        
        $response = $this->actingAs($user)
            ->post(route('checkout.store'), [
                'customer_name' => 'Test Customer',
                'phone' => '08123456789',
                'shipping_address' => 'Alamat pengujian',
                'notes' => '',
                'payment_method' => 'cod',
            ]);
    
        $order = Order::where('user_id', $user->id)
            ->latest('id')
            ->first();
    
        $this->assertNotNull($order);
    
        $response->assertRedirect(route('orders.show', $order));
    
        $this->assertSame(1, $order->items()->count());
    
        $this->assertSame(5, (int) $order->items()->sum('quantity'));
    
        $this->assertEquals(100000, (float) $order->subtotal);
        $this->assertEquals(100000, (float) $order->total);
    
        // Stok harus dikurangi sebanyak 2 + 3, bukan hanya satu baris.
        $this->assertSame(5, (int) $inventory->fresh()->quantity);
    
        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'gross_amount' => 100000,
            'provider' => 'cod',
        ]);
        
        $reservation = InventoryReservation::query()
            ->where('reference_type', Order::class)
            ->where('reference_id', $order->id)
            ->sole();
        
        $this->assertSame(5, (int) $reservation->quantity);
        $this->assertSame(
            InventoryReservation::STATUS_COMMITTED,
            $reservation->status
        );
        
        $this->assertSame(0, (int) $inventory->fresh()->reserved_quantity);
    }

    public function test_checkout_rejects_insufficient_stock_for_selected_variant(): void
    {
        $user = User::factory()->create();
    
        $product = Product::factory()->create([
            'stock' => 10,
        ]);
    
        $defaultVariant = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'TEST-AVAILABLE-'.uniqid(),
            'name' => 'Default',
            'price' => 10000,
            'is_default' => true,
            'status' => 'active',
        ]);
    
        $selectedVariant = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'TEST-LOW-STOCK-'.uniqid(),
            'name' => 'Limited',
            'price' => 30000,
            'is_default' => false,
            'status' => 'active',
        ]);
    
        Inventory::create([
            'product_variant_id' => $defaultVariant->id,
            'quantity' => 10,
            'reserved_quantity' => 0,
            'reorder_level' => 0,
        ]);
    
        $selectedInventory = Inventory::create([
            'product_variant_id' => $selectedVariant->id,
            'quantity' => 3,
            'reserved_quantity' => 1,
            'reorder_level' => 0,
        ]);
    
        $cart = Cart::create([
            'user_id' => $user->id,
        ]);
    
        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'product_variant_id' => $selectedVariant->id,
            'quantity' => 3,
        ]);
    
        $ordersBefore = Order::count();
        $paymentsBefore = Payment::count();
    
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
    
        // Quantity 3 dikurangi reserved 1 berarti hanya tersedia 2.
        $this->assertSame(3, (int) $selectedInventory->fresh()->quantity);
    
        $this->assertSame(1, (int) $selectedInventory->fresh()->reserved_quantity);
    
        $this->assertSame(1, $cart->items()->count());
    }
    
    public function test_checkout_rejects_inactive_variant_without_creating_order(): void {
        
        $user = User::factory()->create();
    
        $product = Product::factory()->create([
            'stock' => 10,
        ]);
    
        $defaultVariant = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'TEST-AVAILABLE-'.uniqid(),
            'name' => 'Default',
            'price' => 10000,
            'is_default' => true,
            'status' => 'inactive',
        ]);
    
        $selectedVariant = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'TEST-LOW-STOCK-'.uniqid(),
            'name' => 'Limited',
            'price' => 30000,
            'is_default' => false,
            'status' => 'inactive',
        ]);
    
        Inventory::create([
            'product_variant_id' => $defaultVariant->id,
            'quantity' => 10,
            'reserved_quantity' => 0,
            'reorder_level' => 0,
        ]);
    
        $selectedInventory = Inventory::create([
            'product_variant_id' => $selectedVariant->id,
            'quantity' => 3,
            'reserved_quantity' => 1,
            'reorder_level' => 0,
        ]);
    
        $cart = Cart::create([
            'user_id' => $user->id,
        ]);
    
        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'product_variant_id' => $selectedVariant->id,
            'quantity' => 3,
        ]);
        
        $ordersBefore = Order::count();
        $paymentsBefore = Payment::count();
    
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
    
        // Quantity 3 dikurangi reserved 1 berarti hanya tersedia 2.
        $this->assertSame(3, (int) $selectedInventory->fresh()->quantity);
    
        $this->assertSame(1, (int) $selectedInventory->fresh()->reserved_quantity);
    
        $this->assertSame(1, $cart->items()->count());
    }
    
    public function test_checkout_rejects_combined_quantity_above_available_stock(): void {
        
        $user = User::factory()->create();
    
        $product = Product::factory()->create([
            'stock' => 10,
        ]);
    
        $firstVariant = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'TEST-AVAILABLE-'.uniqid(),
            'name' => 'Default',
            'price' => 10000,
            'is_default' => true,
            'status' => 'active',
        ]);
    
        $secondVariant = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'TEST-AVAILABLE-'.uniqid(),
            'name' => 'Default',
            'price' => 10000,
            'is_default' => true,
            'status' => 'active',
        ]);

        $firstInventory = Inventory::create([
            'product_variant_id' => $firstVariant->id,
            'quantity' => 6,
            'reserved_quantity' => 1,
            'reorder_level' => 0,
        ]);
    
        $secondInventory = Inventory::create([
            'product_variant_id' => $secondVariant->id,
            'quantity' => 6,
            'reserved_quantity' => 1,
            'reorder_level' => 0,
        ]);

        $cart = Cart::create([
            'user_id' => $user->id,
        ]);
    
        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'product_variant_id' => $firstVariant->id,
            'quantity' => 6,
        ], [
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'product_variant_id' => $secondVariant->id,
            'quantity' => 6,
        ]);
        
        $ordersBefore = Order::count();
        $paymentsBefore = Payment::count();
    
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
    
        $this->assertSame(6, (int) $firstInventory->fresh()->quantity);
    
        $this->assertSame(1, (int) $secondInventory->fresh()->reserved_quantity);
    }
    
    public function test_stripe_checkout_keeps_reservation_without_reducing_physical_stock(): void
    {
        $this->assertGatewayCheckoutRemainsReserved('stripe');
    }
    
    public function test_midtrans_checkout_keeps_reservation_without_reducing_physical_stock(): void
    {
        $this->assertGatewayCheckoutRemainsReserved('midtrans');
    }
    
    private function assertGatewayCheckoutRemainsReserved(string $paymentMethod): void
    {
        $user = User::factory()->create();
    
        $product = Product::factory()->create([
            'price' => 20000,
            'stock' => 10,
        ]);
    
        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'TEST-'.$paymentMethod.'-'.uniqid(),
            'name' => 'Gateway Variant',
            'price' => 20000,
            'is_default' => true,
            'status' => 'active',
        ]);
    
        $inventory = Inventory::create([
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
            'quantity' => 3,
        ]);
    
        $response = $this->actingAs($user)
            ->post(route('checkout.store'), [
                'customer_name' => 'Test Customer',
                'phone' => '08123456789',
                'shipping_address' => 'Alamat pengujian',
                'notes' => '',
                'payment_method' => $paymentMethod,
            ]);
    
        $order = Order::query()
            ->where('user_id', $user->id)
            ->latest('id')
            ->first();
    
        $this->assertNotNull($order);
    
        $expectedRoute = $paymentMethod === 'midtrans'
            ? 'payment.midtrans.show'
            : 'payment.show';
    
        $response->assertRedirect(route($expectedRoute, $order));
    
        $reservation = InventoryReservation::query()
            ->where('reference_type', Order::class)
            ->where('reference_id', $order->id)
            ->sole();
    
        $this->assertSame(
            InventoryReservation::STATUS_RESERVED,
            $reservation->status
        );
    
        $this->assertSame(3, (int) $reservation->quantity);
        $this->assertSame(10, (int) $inventory->fresh()->quantity);
        $this->assertSame(3, (int) $inventory->fresh()->reserved_quantity);
    
        $this->assertDatabaseMissing('cart_items', [
            'cart_id' => $cart->id,
        ]);
    }
    
    public function test_checkout_rolls_back_all_reservations_and_keeps_cart_when_reservation_fails(): void
    {
        $user = User::factory()->create();
    
        $product = Product::factory()->create([
            'price' => 20000,
            'stock' => 20,
        ]);
    
        $firstVariant = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'TEST-ROLLBACK-A-'.uniqid(),
            'name' => 'Variant A',
            'price' => 20000,
            'is_default' => true,
            'status' => 'active',
        ]);
    
        $secondVariant = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'TEST-ROLLBACK-B-'.uniqid(),
            'name' => 'Variant B',
            'price' => 20000,
            'is_default' => false,
            'status' => 'active',
        ]);
    
        $firstInventory = Inventory::create([
            'product_variant_id' => $firstVariant->id,
            'quantity' => 10,
            'reserved_quantity' => 0,
            'reorder_level' => 0,
        ]);
    
        $secondInventory = Inventory::create([
            'product_variant_id' => $secondVariant->id,
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
            'product_variant_id' => $firstVariant->id,
            'quantity' => 2,
        ]);
    
        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'product_variant_id' => $secondVariant->id,
            'quantity' => 3,
        ]);
    
        $ordersBefore = Order::count();
        $paymentsBefore = Payment::count();
    
        $realService = new VariantInventoryService();
        $reservationCalls = 0;
    
        $mock = \Mockery::mock(VariantInventoryService::class)->makePartial();
    
        $mock->shouldReceive('reserve')
            ->twice()
            ->andReturnUsing(
                function (
                    $variant,
                    $quantity,
                    $idempotencyKey,
                    $referenceType,
                    $referenceId
                ) use ($realService, &$reservationCalls) {
                    $reservationCalls++;
    
                    if ($reservationCalls === 2) {
                        throw ValidationException::withMessages([
                            'stock' => 'Simulasi kegagalan reservasi varian kedua.',
                        ]);
                    }
    
                    return $realService->reserve(
                        $variant,
                        $quantity,
                        $idempotencyKey,
                        $referenceType,
                        $referenceId
                    );
                }
            );
    
        $this->app->instance(VariantInventoryService::class, $mock);
    
        $response = $this->actingAs($user)
            ->from(route('checkout.index'))
            ->post(route('checkout.store'), [
                'customer_name' => 'Test Customer',
                'phone' => '08123456789',
                'shipping_address' => 'Alamat pengujian',
                'notes' => '',
                'payment_method' => 'stripe',
            ]);
    
        $response->assertRedirect(route('checkout.index'));
        $response->assertSessionHasErrors('stock');
    
        $this->assertSame(2, $reservationCalls);
        $this->assertSame($ordersBefore, Order::count());
        $this->assertSame($paymentsBefore, Payment::count());
    
        $this->assertSame(10, (int) $firstInventory->fresh()->quantity);
        $this->assertSame(0, (int) $firstInventory->fresh()->reserved_quantity);
    
        $this->assertSame(10, (int) $secondInventory->fresh()->quantity);
        $this->assertSame(0, (int) $secondInventory->fresh()->reserved_quantity);
    
        $this->assertSame(
            0,
            InventoryReservation::query()
                ->where('reference_type', Order::class)
                ->count()
        );
    
        $this->assertSame(2, $cart->items()->count());
    }
}

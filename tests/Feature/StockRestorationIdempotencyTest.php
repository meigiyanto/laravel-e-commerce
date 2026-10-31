<?php

namespace Tests\Feature;

use App\Http\Controllers\PaymentController;
use App\Models\Inventory;
use App\Models\InventoryMovement;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Services\StripePaymentService;
use App\Services\VariantInventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use LogicException;
use Mockery;
use ReflectionMethod;
use Stripe\PaymentIntent;
use Tests\TestCase;

class StockRestorationIdempotencyTest extends TestCase
{
    use RefreshDatabase;

    private function createInventoryFixture(int $quantity = 10): array
    {
        $product = Product::factory()->create([
            'stock' => $quantity,
        ]);

        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'TEST-'.uniqid(),
            'name' => 'Test Variant',
            'price' => 100000,
            'is_default' => true,
            'status' => 'active',
        ]);

        $inventory = Inventory::create([
            'product_variant_id' => $variant->id,
            'quantity' => $quantity,
            'reserved_quantity' => 0,
            'reorder_level' => 0,
        ]);

        return compact('product', 'variant', 'inventory');
    }

    private function createOrder(): Order
    {
        return Order::factory()->create([
            'status' => 'pending',
        ]);
    }

    private function createOrderItem(
        Order $order,
        Product $product,
        ProductVariant $variant,
        int $quantity
    ): OrderItem {
        return OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'product_name' => $product->name,
            'price' => 100000,
            'quantity' => $quantity,
            'subtotal' => 100000 * $quantity,
        ]);
    }

    public function test_deduct_for_order_only_deducts_stock_once_for_same_idempotency_key(): void
    {
        $fixture = $this->createInventoryFixture(10);
        $order = $this->createOrder();
        $service = app(VariantInventoryService::class);

        DB::transaction(function () use ($fixture, $order, $service) {
            $service->deductForOrder(
                $fixture['product'],
                $fixture['variant'],
                $fixture['inventory'],
                2,
                $order
            );

            $service->deductForOrder(
                $fixture['product'],
                $fixture['variant'],
                $fixture['inventory'],
                2,
                $order
            );
        });

        $this->assertSame(
            8,
            $fixture['inventory']->fresh()->quantity
        );

        $this->assertSame(
            1,
            InventoryMovement::query()
                ->where('idempotency_key', "order:{$order->id}:variant:{$fixture['variant']->id}:sale")
                ->count()
        );
    }

    public function test_restore_for_order_only_restores_stock_once_for_same_order_item(): void
    {
        $fixture = $this->createInventoryFixture(8);
        $order = $this->createOrder();

        $item = $this->createOrderItem(
            $order,
            $fixture['product'],
            $fixture['variant'],
            2
        );

        $service = app(VariantInventoryService::class);

        DB::transaction(function () use ($fixture, $order, $item, $service) {
            $service->restoreForOrder(
                $fixture['product'],
                $fixture['variant']->id,
                2,
                $order,
                $item->id
            );

            $service->restoreForOrder(
                $fixture['product'],
                $fixture['variant']->id,
                2,
                $order,
                $item->id
            );
        });

        $this->assertSame(
            10,
            $fixture['inventory']->fresh()->quantity
        );

        $this->assertSame(
            1,
            InventoryMovement::query()
                ->where('idempotency_key', "order:{$order->id}:item:{$item->id}:return")
                ->count()
        );
    }

    public function test_restore_for_order_handles_multiple_items_with_same_variant(): void
    {
        $fixture = $this->createInventoryFixture(8);
        $order = $this->createOrder();

        $firstItem = $this->createOrderItem(
            $order,
            $fixture['product'],
            $fixture['variant'],
            1
        );

        $secondItem = $this->createOrderItem(
            $order,
            $fixture['product'],
            $fixture['variant'],
            1
        );

        $service = app(VariantInventoryService::class);

        DB::transaction(function () use ($order, $service) {
            $service->restoreItemsForOrder($order);
        });

        // Pemanggilan ulang tidak boleh mengembalikan stok lagi.
        DB::transaction(function () use ($order, $service) {
            $service->restoreItemsForOrder($order);
        });

        $this->assertSame(
            10,
            $fixture['inventory']->fresh()->quantity
        );

        $this->assertSame(
            2,
            InventoryMovement::query()
                ->where('reference_type', Order::class)
                ->where('reference_id', $order->id)
                ->where('type', 'return')
                ->count()
        );

        $this->assertDatabaseHas('inventory_movements', [
            'idempotency_key' => "order:{$order->id}:item:{$firstItem->id}:return",
            'product_variant_id' => $fixture['variant']->id,
            'reference_type' => Order::class,
            'reference_id' => $order->id,
            'type' => 'return',
            'quantity' => 1,
        ]);

        $this->assertDatabaseHas('inventory_movements', [
            'idempotency_key' => "order:{$order->id}:item:{$secondItem->id}:return",
            'product_variant_id' => $fixture['variant']->id,
            'reference_type' => Order::class,
            'reference_id' => $order->id,
            'type' => 'return',
            'quantity' => 1,
        ]);
    }

    public function test_deduct_for_order_rejects_reusing_key_with_different_data(): void
    {
        $fixture = $this->createInventoryFixture(10);
        $order = $this->createOrder();
        $service = app(VariantInventoryService::class);

        DB::transaction(function () use ($fixture, $order, $service) {
            $service->deductForOrder(
                $fixture['product'],
                $fixture['variant'],
                $fixture['inventory'],
                2,
                $order
            );
        });

        try {
            DB::transaction(function () use ($fixture, $order, $service) {
                $service->deductForOrder(
                    $fixture['product'],
                    $fixture['variant'],
                    $fixture['inventory'],
                    3,
                    $order
                );
            });

            $this->fail(
                'Seharusnya LogicException dilempar ketika data movement berbeda.'
            );
        } catch (LogicException $exception) {
            $this->assertSame(
                'Inventory movement idempotency key digunakan dengan data yang berbeda.',
                $exception->getMessage()
            );
        }

        // Pengurangan pertama tetap berlaku, percobaan kedua tidak mengubah stok.
        $this->assertSame(
            8,
            $fixture['inventory']->fresh()->quantity
        );

        $this->assertSame(
            1,
            InventoryMovement::query()
                ->where('idempotency_key', "order:{$order->id}:variant:{$fixture['variant']->id}:sale")
                ->count()
        );
    }

    public function test_stripe_cancellation_does_not_restore_stock_twice_after_admin_cancellation(): void
    {
        $user = User::factory()->create();

        $product = Product::factory()->create([
            'stock' => 8,
        ]);

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'pending',
            'total' => 200000,
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'price' => 100000,
            'quantity' => 2,
            'subtotal' => 200000,
        ]);

        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'provider' => 'stripe',
            'status' => 'pending',
            'transaction_status' => 'requires_payment_method',
            'gross_amount' => 200000,
            'currency' => 'IDR',
            'stripe_payment_intent_id' => 'pi_stock_cancel_test',
        ]);

        $paymentIntent = PaymentIntent::constructFrom([
            'id' => 'pi_stock_cancel_test',
            'object' => 'payment_intent',
            'status' => 'canceled',
            'amount' => 20000000,
            'currency' => 'idr',
            'metadata' => [
                'order_id' => (string) $order->id,
            ],
        ]);

        $stripeService = Mockery::mock(StripePaymentService::class);

        $stripeService
            ->shouldReceive('cancel')
            ->once()
            ->with('pi_stock_cancel_test')
            ->andReturn($paymentIntent);

        $this->app->instance(
            StripePaymentService::class,
            $stripeService
        );

        $response = $this->actingAs(
            User::factory()->create(['role' => 'admin'])
        )->patch(
            route('admin.orders.status', $order),
            ['status' => 'canceled']
        );

        $response->assertSessionHas('success');

        $this->assertSame('canceled', $order->fresh()->status);
        $this->assertSame(10, $product->fresh()->stock);

        $paymentIntent = PaymentIntent::constructFrom([
            'id' => 'pi_stock_cancel_test',
            'object' => 'payment_intent',
            'status' => 'canceled',
            'amount' => 20000000,
            'currency' => 'idr',
            'metadata' => [
                'order_id' => (string) $order->id,
            ],
        ]);

        $method = new ReflectionMethod(
            PaymentController::class,
            'cancelOrder'
        );

        $method->invoke(
            app(PaymentController::class),
            $order->fresh(),
            $payment->fresh(),
            'canceled',
            $paymentIntent
        );

        $this->assertSame(10, $product->fresh()->stock);
        $this->assertSame('canceled', $order->fresh()->status);
        $this->assertSame('canceled', $payment->fresh()->status);
    }
}

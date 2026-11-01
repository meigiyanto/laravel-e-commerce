<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Inventory;
use App\Models\User;
use App\Models\InventoryReservation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminOrderStatusConsistencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_cannot_cancel_shipped_order(): void
    {
        [$order, $product] = $this->createOrderWithStock(
            'shipped'
        );

        $stockBefore = $product->fresh()->stock;

        $response = $this->actingAs(
            User::factory()->create(['role' => 'admin'])
        )->patch(
            route('admin.orders.status', $order),
            ['status' => 'canceled']
        );

        $order->refresh();
        $product->refresh();

        $this->assertSame('shipped', $order->status);
        $this->assertSame($stockBefore, $product->stock);
    }

    public function test_admin_cannot_cancel_completed_order(): void
    {
        [$order, $product] = $this->createOrderWithStock(
            'completed'
        );

        $stockBefore = $product->fresh()->stock;

        $this->actingAs(
            User::factory()->create(['role' => 'admin'])
        )->patch(
            route('admin.orders.status', $order),
            ['status' => 'canceled']
        );

        $order->refresh();
        $product->refresh();

        $this->assertSame('completed', $order->status);
        $this->assertSame($stockBefore, $product->stock);
    }

    public function test_canceled_order_cannot_be_reactivated(): void
    {
        [$order, $product] = $this->createOrderWithStock(
            'canceled'
        );

        $stockBefore = $product->fresh()->stock;

        $this->actingAs(
            User::factory()->create(['role' => 'admin'])
        )->patch(
            route('admin.orders.status', $order),
            ['status' => 'processing']
        );

        $order->refresh();
        $product->refresh();

        $this->assertSame('canceled', $order->status);
        $this->assertSame($stockBefore, $product->stock);
    }

    private function createOrderWithStock(
        string $status
    ): array {
        $user = User::factory()->create();

        $product = Product::factory()->create([
            'stock' => 8,
        ]);

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => $status,
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

        return [$order, $product];
    }

    public function test_admin_cannot_move_pending_order_directly_to_completed(): void
    {
        [$order, $product] = $this->createOrderWithStock(
            'pending'
        );

        $this->actingAs(
            User::factory()->create(['role' => 'admin'])
        )->patch(
            route('admin.orders.status', $order),
            ['status' => 'completed']
        );

        $this->assertSame(
            'pending',
            $order->fresh()->status
        );

        $this->assertSame(
            8,
            $product->fresh()->stock
        );
    }

    public function test_admin_cannot_move_shipped_order_back_to_processing(): void
    {
        [$order, $product] = $this->createOrderWithStock(
            'shipped'
        );

        $this->actingAs(
            User::factory()->create(['role' => 'admin'])
        )->patch(
            route('admin.orders.status', $order),
            ['status' => 'processing']
        );

        $this->assertSame(
            'shipped',
            $order->fresh()->status
        );

        $this->assertSame(
            8,
            $product->fresh()->stock
        );
    }
    public function test_fulfillment_status_transitions_do_not_deduct_variant_stock_again(): void
    {
        [$order, $product, $variant, $inventory] = $this->createOrderWithVariantStock('pending');
        $admin = User::factory()->create(['role' => 'admin']);

        foreach (['processing', 'shipped', 'completed'] as $status) {
            $this->actingAs($admin)->patch(
                route('admin.orders.status', $order),
                ['status' => $status]
            );
            $this->assertSame($status, $order->fresh()->status);
            $this->assertSame(8, $inventory->fresh()->quantity);
            $this->assertSame(8, $product->fresh()->stock);
        }

        $this->assertDatabaseMissing('inventory_movements', [
            'product_variant_id' => $variant->id,
            'type' => 'return',
            'reference_type' => Order::class,
            'reference_id' => $order->id,
        ]);
    }

    public function test_canceling_processing_order_restores_variant_stock_only_once(): void
    {
        [$order, $product, $variant, $inventory] = $this->createOrderWithVariantStock('processing');
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->patch(
            route('admin.orders.status', $order),
            ['status' => 'canceled']
        );

        $this->assertSame('canceled', $order->fresh()->status);
        $this->assertSame(10, $inventory->fresh()->quantity);
        $this->assertSame(10, $product->fresh()->stock);

        $this->actingAs($admin)->patch(
            route('admin.orders.status', $order),
            ['status' => 'canceled']
        );

        $this->assertSame(10, $inventory->fresh()->quantity);
        $this->assertSame(10, $product->fresh()->stock);
        $this->assertDatabaseCount('inventory_movements', 1);
        $this->assertDatabaseHas('inventory_movements', [
            'product_variant_id' => $variant->id,
            'type' => 'return',
            'quantity' => 2,
            'reference_type' => Order::class,
            'reference_id' => $order->id,
        ]);
    }

    private function createOrderWithVariantStock(string $status): array
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['stock' => 8]);
        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'TEST-STATUS-'.uniqid(),
            'name' => 'Test variant',
            'price' => 100000,
            'is_default' => true,
            'status' => 'active',
        ]);
        $inventory = Inventory::create([
            'product_variant_id' => $variant->id,
            'quantity' => 8,
            'reserved_quantity' => 0,
            'reorder_level' => 0,
        ]);
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => $status,
            'total' => 200000,
        ]);
        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'product_name' => $product->name,
            'price' => 100000,
            'quantity' => 2,
            'subtotal' => 200000,
        ]);

        return [$order, $product, $variant, $inventory];
    }
    
    public function test_canceling_pending_order_releases_reserved_stock_without_restoring_quantity(): void
    {
        [$order, $product, $variant, $inventory] =
            $this->createOrderWithVariantStock('pending');
    
        $reservation = InventoryReservation::create([
            'product_variant_id' => $variant->id,
            'idempotency_key' => "order:{$order->id}:variant:{$variant->id}:reserve",
            'reference_type' => Order::class,
            'reference_id' => $order->id,
            'quantity' => 2,
            'status' => InventoryReservation::STATUS_RESERVED,
        ]);
    
        $inventory->update(['reserved_quantity' => 2]);
    
        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->patch(route('admin.orders.status', $order), [
                'status' => 'canceled',
            ]);
    
        $this->assertSame('canceled', $order->fresh()->status);
        $this->assertSame(8, $inventory->fresh()->quantity);
        $this->assertSame(0, $inventory->fresh()->reserved_quantity);
        $this->assertSame(8, $product->fresh()->stock);
    
        $this->assertSame(
            InventoryReservation::STATUS_RELEASED,
            $reservation->fresh()->status
        );
    
        $this->assertDatabaseMissing('inventory_movements', [
            'product_variant_id' => $variant->id,
            'type' => 'return',
            'reference_type' => Order::class,
            'reference_id' => $order->id,
        ]);
    }
    
    public function test_canceling_pending_order_restores_committed_reservation_only_once(): void
    {
        [$order, $product, $variant, $inventory] =
            $this->createOrderWithVariantStock('pending');
    
        $inventory->update(['quantity' => 6]);
        $product->update(['stock' => 6]);
    
        $reservation = InventoryReservation::create([
            'product_variant_id' => $variant->id,
            'idempotency_key' => "order:{$order->id}:variant:{$variant->id}:reserve",
            'reference_type' => Order::class,
            'reference_id' => $order->id,
            'quantity' => 2,
            'status' => InventoryReservation::STATUS_COMMITTED,
            'committed_at' => now(),
        ]);
    
        $admin = User::factory()->create(['role' => 'admin']);
    
        $this->actingAs($admin)->patch(
            route('admin.orders.status', $order),
            ['status' => 'canceled']
        );
    
        $this->assertSame('canceled', $order->fresh()->status);
        $this->assertSame(8, $inventory->fresh()->quantity);
        $this->assertSame(8, $product->fresh()->stock);
    
        // Status committed tidak diubah menjadi released.
        $this->assertSame(
            InventoryReservation::STATUS_COMMITTED,
            $reservation->fresh()->status
        );
    
        // Request pembatalan kedua tidak boleh memulihkan stok lagi.
        $this->actingAs($admin)->patch(
            route('admin.orders.status', $order),
            ['status' => 'canceled']
        );
    
        $this->assertSame(8, $inventory->fresh()->quantity);
        $this->assertSame(8, $product->fresh()->stock);
    
        $this->assertDatabaseCount('inventory_movements', 1);
        $this->assertDatabaseHas('inventory_movements', [
            'product_variant_id' => $variant->id,
            'type' => 'return',
            'quantity' => 2,
            'reference_type' => Order::class,
            'reference_id' => $order->id,
        ]);
    }
    
    public function test_canceling_order_with_released_reservation_does_not_restore_stock(): void
    {
        [$order, $product, $variant, $inventory] =
            $this->createOrderWithVariantStock('pending');
    
        $reservation = InventoryReservation::create([
            'product_variant_id' => $variant->id,
            'idempotency_key' => "order:{$order->id}:variant:{$variant->id}:reserve",
            'reference_type' => Order::class,
            'reference_id' => $order->id,
            'quantity' => 2,
            'status' => InventoryReservation::STATUS_RELEASED,
            'released_at' => now(),
        ]);
    
        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->patch(route('admin.orders.status', $order), [
                'status' => 'canceled',
            ]);
    
        $this->assertSame('canceled', $order->fresh()->status);
        $this->assertSame(8, $inventory->fresh()->quantity);
        $this->assertSame(8, $product->fresh()->stock);
    
        $this->assertSame(
            InventoryReservation::STATUS_RELEASED,
            $reservation->fresh()->status
        );
    
        $this->assertDatabaseMissing('inventory_movements', [
            'product_variant_id' => $variant->id,
            'type' => 'return',
            'reference_type' => Order::class,
            'reference_id' => $order->id,
        ]);
    }
}

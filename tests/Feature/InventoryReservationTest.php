<?php

namespace Tests\Feature;

use App\Models\Inventory;
use App\Models\InventoryMovement;
use App\Models\InventoryReservation;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\VariantInventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use LogicException;
use Tests\TestCase;

class InventoryReservationTest extends TestCase
{
    use RefreshDatabase;

    private function createFixture(int $stock = 10): array
    {
        $product = Product::factory()->create([
            'stock' => $stock,
        ]);

        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'RESERVE-'.uniqid(),
            'name' => 'Reservation Variant',
            'price' => 100000,
            'is_default' => true,
            'status' => 'active',
        ]);

        $inventory = Inventory::create([
            'product_variant_id' => $variant->id,
            'quantity' => $stock,
            'reserved_quantity' => 0,
            'reorder_level' => 0,
        ]);

        $order = Order::factory()->create();

        return compact('product', 'variant', 'inventory', 'order');
    }

    private function reserveFixture(
        array $fixture,
        int $quantity,
        string $key
    ): InventoryReservation {
        return app(VariantInventoryService::class)->reserve(
            $fixture['variant'],
            $quantity,
            $key,
            Order::class,
            (int) $fixture['order']->id
        );
    }

    public function test_reserve_increases_reserved_quantity_without_reducing_physical_stock(): void
    {
        $fixture = $this->createFixture(10);

        $reservation = $this->reserveFixture(
            $fixture,
            3,
            'order:'.$fixture['order']->id.':reservation:1'
        );

        $inventory = $fixture['inventory']->fresh();

        $this->assertSame(10, $inventory->quantity);
        $this->assertSame(3, $inventory->reserved_quantity);
        $this->assertSame(7, $inventory->available_quantity);
        $this->assertSame(
            InventoryReservation::STATUS_RESERVED,
            $reservation->status
        );
    }

    public function test_reserve_is_idempotent_for_same_key_and_payload(): void
    {
        $fixture = $this->createFixture(10);
        $key = 'order:'.$fixture['order']->id.':reservation:2';

        $service = app(VariantInventoryService::class);

        $first = $this->reserveFixture($fixture, 3, $key);
        $second = $this->reserveFixture($fixture, 3, $key);

        $this->assertSame($first->id, $second->id);
        $this->assertSame(3, $fixture['inventory']->fresh()->reserved_quantity);

        $this->assertSame(
            1,
            InventoryReservation::query()
                ->where('idempotency_key', $key)
                ->count()
        );
    }

    public function test_reserve_rejects_same_key_with_different_payload(): void
    {
        $fixture = $this->createFixture(10);
        $key = 'order:'.$fixture['order']->id.':reservation:3';

        $this->reserveFixture($fixture, 2, $key);

        try {
            $this->reserveFixture($fixture, 3, $key);
            $this->fail('Seharusnya LogicException dilempar.');
        } catch (LogicException $exception) {
            $this->assertSame(
                'Reservation idempotency key digunakan dengan data berbeda.',
                $exception->getMessage()
            );
        }

        $this->assertSame(
            2,
            $fixture['inventory']->fresh()->reserved_quantity
        );
    }

    public function test_reserve_rejects_insufficient_available_stock(): void
    {
        $fixture = $this->createFixture(5);

        $this->reserveFixture(
            $fixture,
            4,
            'order:'.$fixture['order']->id.':reservation:4'
        );

        try {
            $this->reserveFixture(
                $fixture,
                2,
                'order:'.$fixture['order']->id.':reservation:5'
            );

            $this->fail('Seharusnya ValidationException dilempar.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey(
                'stock',
                $exception->errors()
            );
        }

        $this->assertSame(5, $fixture['inventory']->fresh()->quantity);
        $this->assertSame(4, $fixture['inventory']->fresh()->reserved_quantity);

        $this->assertDatabaseMissing('inventory_reservations', [
            'idempotency_key' => 'order:'.$fixture['order']->id.':reservation:5',
        ]);
    }

    public function test_commit_reservation_reduces_stock_and_is_idempotent(): void
    {
        $fixture = $this->createFixture(10);
        $key = 'order:'.$fixture['order']->id.':reservation:6';

        $this->reserveFixture($fixture, 3, $key);

        $service = app(VariantInventoryService::class);

        $first = $service->commitReservation($key);
        $second = $service->commitReservation($key);

        $inventory = $fixture['inventory']->fresh();

        $this->assertSame(7, $inventory->quantity);
        $this->assertSame(0, $inventory->reserved_quantity);
        $this->assertSame(
            InventoryReservation::STATUS_COMMITTED,
            $first->status
        );
        $this->assertSame($first->id, $second->id);

        $this->assertSame(
            1,
            InventoryMovement::query()
                ->where('idempotency_key', "reservation:{$first->id}:commit")
                ->where('type', 'sale')
                ->where('quantity', -3)
                ->count()
        );
    }

    public function test_release_reservation_does_not_reduce_stock_and_is_idempotent(): void
    {
        $fixture = $this->createFixture(10);
        $key = 'order:'.$fixture['order']->id.':reservation:7';

        $this->reserveFixture($fixture, 3, $key);

        $service = app(VariantInventoryService::class);

        $first = $service->releaseReservation($key);
        $second = $service->releaseReservation($key);

        $inventory = $fixture['inventory']->fresh();

        $this->assertSame(10, $inventory->quantity);
        $this->assertSame(0, $inventory->reserved_quantity);
        $this->assertSame(10, $inventory->available_quantity);
        $this->assertSame(
            InventoryReservation::STATUS_RELEASED,
            $first->status
        );
        $this->assertSame($first->id, $second->id);

        $this->assertSame(
            0,
            InventoryMovement::query()
                ->where('reference_type', Order::class)
                ->where('reference_id', $fixture['order']->id)
                ->count()
        );
    }

    public function test_committed_and_released_reservations_cannot_change_state_again(): void
    {
        $fixture = $this->createFixture(10);
        $service = app(VariantInventoryService::class);

        $releasedKey = 'order:'.$fixture['order']->id.':reservation:8';
        $committedKey = 'order:'.$fixture['order']->id.':reservation:9';

        $this->reserveFixture($fixture, 2, $releasedKey);
        $service->releaseReservation($releasedKey);

        try {
            $service->commitReservation($releasedKey);
            $this->fail('Reservasi released tidak boleh di-commit.');
        } catch (LogicException $exception) {
            $this->assertSame(
                'Reservasi yang sudah dilepas tidak dapat di-commit.',
                $exception->getMessage()
            );
        }

        $this->reserveFixture($fixture, 2, $committedKey);
        $service->commitReservation($committedKey);

        try {
            $service->releaseReservation($committedKey);
            $this->fail('Reservasi committed tidak boleh di-release.');
        } catch (LogicException $exception) {
            $this->assertSame(
                'Reservasi yang sudah di-commit tidak dapat dilepas.',
                $exception->getMessage()
            );
        }

        $this->assertSame(8, $fixture['inventory']->fresh()->quantity);
        $this->assertSame(0, $fixture['inventory']->fresh()->reserved_quantity);
    }
}

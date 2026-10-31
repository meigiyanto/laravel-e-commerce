<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class InventoryIntegrityTest extends TestCase
{
    use RefreshDatabase;

    private function createVariant(): ProductVariant
    {
        $product = Product::factory()->create();

        return ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'TEST-'.uniqid(),
            'name' => 'Test Variant',
            'price' => 10000,
            'is_default' => true,
            'status' => 'active',
        ]);
    }

    private function insertInventory(
        int $variantId,
        int $quantity,
        int $reservedQuantity,
    ): void {
        DB::table('inventories')->insert([
            'product_variant_id' => $variantId,
            'quantity' => $quantity,
            'reserved_quantity' => $reservedQuantity,
            'reorder_level' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_database_rejects_inventory_insert_when_reserved_quantity_exceeds_quantity(): void
    {
        $variant = $this->createVariant();

        $this->expectException(QueryException::class);

        $this->insertInventory(
            $variant->id,
            10,
            11,
        );
    }

    public function test_database_rejects_inventory_update_when_reserved_quantity_exceeds_quantity(): void
    {
        $variant = $this->createVariant();

        $this->insertInventory(
            $variant->id,
            10,
            5,
        );

        $this->expectException(QueryException::class);

        DB::table('inventories')
            ->where('product_variant_id', $variant->id)
            ->update([
                'reserved_quantity' => 11,
            ]);
    }

    public function test_database_accepts_inventory_insert_when_reserved_quantity_is_valid(): void
    {
        $variant = $this->createVariant();

        $this->insertInventory(
            $variant->id,
            10,
            5,
        );

        $this->assertDatabaseHas('inventories', [
            'product_variant_id' => $variant->id,
            'quantity' => 10,
            'reserved_quantity' => 5,
        ]);
    }

    public function test_database_accepts_inventory_update_when_reserved_quantity_is_valid(): void
    {
        $variant = $this->createVariant();

        $this->insertInventory(
            $variant->id,
            10,
            5,
        );

        DB::table('inventories')
            ->where('product_variant_id', $variant->id)
            ->update([
                'quantity' => 8,
                'reserved_quantity' => 8,
            ]);

        $this->assertDatabaseHas('inventories', [
            'product_variant_id' => $variant->id,
            'quantity' => 8,
            'reserved_quantity' => 8,
        ]);
    }
}

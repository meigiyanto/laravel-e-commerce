<?php

namespace App\Services;

use App\Models\Inventory;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class VariantInventoryService
{
    /**
     * Resolusi varian dan inventaris di dalam transaksi.
     *
     * products.stock hanya digunakan untuk backfill awal jika
     * belum ada inventaris untuk varian mana pun dari produk.
     */
    public function resolve(
        Product $product,
        ?int $variantId = null
    ): array {
        $lockedProduct = Product::query()
            ->whereKey($product->id)
            ->lockForUpdate()
            ->firstOrFail();

        $variants = ProductVariant::query()
            ->where('product_id', $lockedProduct->id)
            ->orderByDesc('is_default')
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        if ($variants->isEmpty()) {
            $baseSku = 'LEGACY-'.$lockedProduct->id;
            $sku = $baseSku;
            $suffix = 1;

            while (ProductVariant::where('sku', $sku)->exists()) {
                $sku = $baseSku.'-'.$suffix++;
            }

            $variant = ProductVariant::create([
                'product_id' => $lockedProduct->id,
                'sku' => $sku,
                'name' => $lockedProduct->name,
                'price' => $lockedProduct->price ?? 0,
                'is_default' => true,
                'status' => 'active',
            ]);

            $variants = collect([$variant]);
        }

        $variant = $variantId !== null
            ? $variants->firstWhere('id', $variantId)
            : $variants->first();

        if (! $variant) {
            throw ValidationException::withMessages([
                'stock' => 'Varian tidak ditemukan untuk produk ini.',
            ]);
        }

        /*
         * Produk yang belum memiliki inventaris sama sekali
         * masih mungkin menggunakan stok legacy.
         *
         * Alokasikan stok legacy hanya ke varian default,
         * bukan ke setiap varian.
         */
        $variantIds = $variants->pluck('id');

        $hasAnyInventory = Inventory::query()
            ->whereIn('product_variant_id', $variantIds)
            ->exists();

        if (! $hasAnyInventory) {
            $legacyVariant = $variants->firstWhere(
                'is_default',
                true
            ) ?? $variants->first();

            $legacyStock = max(
                0,
                (int) $lockedProduct->stock
            );

            foreach ($variants as $candidate) {
                $initialQuantity = $candidate->is($legacyVariant)
                    ? $legacyStock
                    : 0;

                $inventory = Inventory::create([
                    'product_variant_id' => $candidate->id,
                    'quantity' => $initialQuantity,
                    'reserved_quantity' => 0,
                    'reorder_level' => 0,
                ]);

                if ($initialQuantity > 0) {
                    $candidate->inventoryMovements()->create([
                        'type' => 'opening_stock',
                        'quantity' => $initialQuantity,
                        'note' => 'Backfill stok legacy ke varian default.',
                        'created_by' => auth()->id(),
                    ]);
                }
            }
        } else {
            /*
             * Jika inventaris sudah mulai digunakan, varian
             * yang belum memiliki baris inventaris dimulai
             * dari nol, bukan menyalin ulang products.stock.
             */
            foreach ($variants as $candidate) {
                Inventory::firstOrCreate(
                    ['product_variant_id' => $candidate->id],
                    [
                        'quantity' => 0,
                        'reserved_quantity' => 0,
                        'reorder_level' => 0,
                    ]
                );
            }
        }

        $inventory = Inventory::query()
            ->where('product_variant_id', $variant->id)
            ->lockForUpdate()
            ->firstOrFail();

        return [
            'product' => $lockedProduct,
            'variant' => $variant,
            'inventory' => $inventory,
        ];
    }

    /**
     * Kurangi stok saat order dibuat.
     * Pola saat ini mengurangi quantity langsung;
     * reserved_quantity tidak dinaikkan.
     */
    public function deductForOrder(
        Product $product,
        ProductVariant $variant,
        Inventory $inventory,
        int $quantity,
        Order $order
    ): void {
        $lockedInventory = Inventory::query()
            ->whereKey($inventory->id)
            ->lockForUpdate()
            ->firstOrFail();

        $available = (int) $lockedInventory->quantity
            - (int) $lockedInventory->reserved_quantity;

        if ($quantity < 1 || $quantity > $available) {
            throw ValidationException::withMessages([
                'stock' => "Stok varian {$variant->name} tidak mencukupi.",
            ]);
        }

        $lockedInventory->update([
            'quantity' => (int) $lockedInventory->quantity - $quantity,
        ]);

        $variant->inventoryMovements()->create([
            'type' => 'sale',
            'quantity' => -$quantity,
            'reference_type' => Order::class,
            'reference_id' => $order->id,
            'note' => 'Stok dikurangi saat checkout.',
            'created_by' => auth()->id(),
        ]);

        $this->syncLegacyStock($product);
    }

    /**
     * Kembalikan stok pada transisi order menuju canceled.
     *
     * Pemanggil wajib mengunci order dan hanya memanggil
     * method ini satu kali pada transisi yang sah.
     */
    public function restoreForOrder(
        Product $product,
        ?int $variantId,
        int $quantity,
        Order $order
    ): void {
        if ($quantity < 1) {
            return;
        }

        $resolved = $this->resolve($product, $variantId);

        $lockedProduct = $resolved['product'];
        $variant = $resolved['variant'];
        $inventory = $resolved['inventory'];

        $inventory->update([
            'quantity' => (int) $inventory->quantity + $quantity,
        ]);

        $variant->inventoryMovements()->create([
            'type' => 'return',
            'quantity' => $quantity,
            'reference_type' => Order::class,
            'reference_id' => $order->id,
            'note' => 'Stok dikembalikan karena pesanan dibatalkan.',
            'created_by' => auth()->id(),
        ]);

        $this->syncLegacyStock($lockedProduct);
    }

    /**
     * Kolom legacy mencerminkan total quantity seluruh varian.
     * Nilai ini untuk kompatibilitas, bukan validasi pembelian.
     */
    private function syncLegacyStock(Product $product): void
    {
        $totalQuantity = Inventory::query()
            ->whereHas('variant', function ($query) use ($product) {
                $query->where('product_id', $product->id);
            })
            ->sum('quantity');

        $product->update([
            'stock' => (int) $totalQuantity,
        ]);
    }
}

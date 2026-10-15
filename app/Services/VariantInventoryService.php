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
     * Ambil inventaris varian yang akan digunakan.
     *
     * Jika $variantId null, gunakan varian default.
     * Stok legacy dimigrasikan hanya ketika inventory belum ada.
     */
    public function resolve(
        Product $product,
        ?int $variantId = null
    ): array {
        $lockedProduct = Product::query()
            ->whereKey($product->id)
            ->lockForUpdate()
            ->firstOrFail();

        $variantQuery = ProductVariant::query()
            ->where('product_id', $lockedProduct->id);

        if ($variantId !== null) {
            $variantQuery->whereKey($variantId);
        } else {
            $variantQuery
                ->orderByDesc('is_default')
                ->orderBy('id');
        }

        $variant = $variantQuery
            ->lockForUpdate()
            ->first();

        if (! $variant && $variantId !== null) {
            throw ValidationException::withMessages([
                'stock' => 'Varian produk tidak ditemukan.',
            ]);
        }

        if (! $variant) {
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
        }

        $inventory = Inventory::query()
            ->where('product_variant_id', $variant->id)
            ->lockForUpdate()
            ->first();

        if (! $inventory) {
            // Migrasi stok produk lama tepat satu kali.
            $legacyStock = max(0, (int) $lockedProduct->stock);

            $inventory = Inventory::create([
                'product_variant_id' => $variant->id,
                'quantity' => $legacyStock,
                'reserved_quantity' => 0,
                'reorder_level' => 0,
            ]);

            if ($legacyStock > 0) {
                $variant->inventoryMovements()->create([
                    'type' => 'opening_stock',
                    'quantity' => $legacyStock,
                    'note' => 'Migrasi stok legacy ke inventaris varian.',
                    'created_by' => auth()->id(),
                ]);
            }
        }

        // Kolom lama hanya salinan kompatibilitas.
        $lockedProduct->update([
            'stock' => (int) $inventory->quantity,
            'price' => $variant->price,
        ]);

        return [
            'product' => $lockedProduct,
            'variant' => $variant,
            'inventory' => $inventory,
        ];
    }

    /**
     * Kurangi stok ketika order dibuat.
     * reserved_quantity tidak ditambah karena pola aplikasi
     * saat ini langsung mengurangi quantity saat checkout.
     */
    public function deductForOrder(
        Product $product,
        ProductVariant $variant,
        Inventory $inventory,
        int $quantity,
        Order $order
    ): void {
        $available = (int) $inventory->quantity
            - (int) $inventory->reserved_quantity;

        if ($quantity < 1 || $quantity > $available) {
            throw ValidationException::withMessages([
                'stock' => "Stok varian {$variant->name} tidak mencukupi.",
            ]);
        }

        $inventory->update([
            'quantity' => (int) $inventory->quantity - $quantity,
        ]);

        $variant->inventoryMovements()->create([
            'type' => 'sale',
            'quantity' => -$quantity,
            'reference_type' => Order::class,
            'reference_id' => $order->id,
            'note' => 'Stok dikurangi saat checkout.',
            'created_by' => auth()->id(),
        ]);

        $product->update([
            'stock' => (int) $inventory->quantity,
        ]);
    }

    /**
     * Kembalikan stok saat order berhasil dibatalkan.
     * Panggil hanya pada transisi status menuju canceled.
     */
    public function restoreForOrder(
        Product $product,
        ?int $variantId,
        int $quantity,
        Order $order
    ): void {
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

        $lockedProduct->update([
            'stock' => (int) $inventory->quantity,
        ]);
    }
}
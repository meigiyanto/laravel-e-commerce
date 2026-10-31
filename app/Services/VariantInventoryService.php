<?php

namespace App\Services;

use App\Models\Inventory;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\DB;
use App\Models\InventoryMovement;
use Illuminate\Validation\ValidationException;
use App\Models\InventoryReservation;

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
    
    public function reserve(
        ProductVariant $variant,
        int $quantity,
        string $idempotencyKey,
        string $referenceType,
        int $referenceId
    ): InventoryReservation {
        if ($quantity < 1) {
            throw ValidationException::withMessages([
                'stock' => 'Jumlah reservasi tidak valid.',
            ]);
        }
    
        if (
            trim($idempotencyKey) === ''
            || strlen($idempotencyKey) > 191
            || trim($referenceType) === ''
            || strlen($referenceType) > 100
            || $referenceId < 1
        ) {
            throw new \InvalidArgumentException(
                'Data reservasi tidak valid.'
            );
        }
    
        return DB::transaction(function () use (
            $variant,
            $quantity,
            $idempotencyKey,
            $referenceType,
            $referenceId
        ) {
            /*
             * Insert reservation lebih dahulu agar unique key menjadi
             * titik sinkronisasi untuk request dengan key yang sama.
             * Jika stok tidak cukup, seluruh transaksi akan rollback.
             */
            $inserted = DB::table('inventory_reservations')
                ->insertOrIgnore([
                    'product_variant_id' => $variant->id,
                    'idempotency_key' => $idempotencyKey,
                    'reference_type' => $referenceType,
                    'reference_id' => $referenceId,
                    'quantity' => $quantity,
                    'status' => InventoryReservation::STATUS_RESERVED,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
    
            $reservation = InventoryReservation::query()
                ->where('idempotency_key', $idempotencyKey)
                ->lockForUpdate()
                ->firstOrFail();
    
            if ($inserted === 0) {
                if (
                    (int) $reservation->product_variant_id !== (int) $variant->id
                    || (int) $reservation->quantity !== $quantity
                    || $reservation->reference_type !== $referenceType
                    || (int) $reservation->reference_id !== $referenceId
                ) {
                    throw new \LogicException(
                        'Reservation idempotency key digunakan dengan data berbeda.'
                    );
                }
    
                return $reservation;
            }
    
            $product = Product::query()
                ->findOrFail($variant->product_id);
    
            $resolved = $this->resolve($product, (int) $variant->id);
            $inventory = $resolved['inventory'];
    
            /*
             * Kondisi stok diperiksa langsung pada UPDATE agar
             * reservasi tidak dapat melampaui stok tersedia.
             */
            $updated = Inventory::query()
                ->whereKey($inventory->id)
                ->whereRaw(
                    'quantity - reserved_quantity >= ?',
                    [$quantity]
                )
                ->increment('reserved_quantity', $quantity);
    
            if ($updated !== 1) {
                throw ValidationException::withMessages([
                    'stock' => "Stok varian {$resolved['variant']->name} tidak mencukupi.",
                ]);
            }
    
            return $reservation->fresh();
        });
    }
    
    public function commitReservation(
        string $idempotencyKey
    ): InventoryReservation {
        return DB::transaction(function () use ($idempotencyKey) {
            $reservation = InventoryReservation::query()
                ->where('idempotency_key', $idempotencyKey)
                ->lockForUpdate()
                ->firstOrFail();
    
            if ($reservation->status === InventoryReservation::STATUS_COMMITTED) {
                return $reservation;
            }
    
            if ($reservation->status === InventoryReservation::STATUS_RELEASED) {
                throw new \LogicException(
                    'Reservasi yang sudah dilepas tidak dapat di-commit.'
                );
            }
    
            $variant = ProductVariant::query()
                ->findOrFail($reservation->product_variant_id);
    
            $product = Product::query()
                ->findOrFail($variant->product_id);
    
            $resolved = $this->resolve($product, (int) $variant->id);
            $inventory = $resolved['inventory'];
            $quantity = (int) $reservation->quantity;
    
            if (
                (int) $inventory->reserved_quantity < $quantity
                || (int) $inventory->quantity < $quantity
            ) {
                throw new \LogicException(
                    'Jumlah stok tidak konsisten dengan reservasi.'
                );
            }
    
            $movementKey = "reservation:{$reservation->id}:commit";
    
            if ($this->hasRecordedMovement(
                $movementKey,
                (int) $variant->id,
                'sale',
                -$quantity,
                $reservation->reference_type,
                (int) $reservation->reference_id
            )) {
                throw new \LogicException(
                    'Movement commit sudah tercatat tetapi status reservasi belum committed.'
                );
            }
    
            /*
             * Urutan ini penting agar constraint
             * reserved_quantity <= quantity selalu terpenuhi.
             */
            $inventory->decrement('reserved_quantity', $quantity);
            $inventory->decrement('quantity', $quantity);
    
            $variant->inventoryMovements()->create([
                'type' => 'sale',
                'quantity' => -$quantity,
                'reference_type' => $reservation->reference_type,
                'reference_id' => $reservation->reference_id,
                'idempotency_key' => $movementKey,
                'note' => 'Stok dikurangi setelah reservasi di-commit.',
                'created_by' => auth()->id(),
            ]);
    
            $reservation->update([
                'status' => InventoryReservation::STATUS_COMMITTED,
                'committed_at' => now(),
            ]);
    
            $this->syncLegacyStock($product);
    
            return $reservation->fresh();
        });
    }
    
    public function releaseReservation(
        string $idempotencyKey
    ): InventoryReservation {
        return DB::transaction(function () use ($idempotencyKey) {
            $reservation = InventoryReservation::query()
                ->where('idempotency_key', $idempotencyKey)
                ->lockForUpdate()
                ->firstOrFail();
    
            if ($reservation->status === InventoryReservation::STATUS_RELEASED) {
                return $reservation;
            }
    
            if ($reservation->status === InventoryReservation::STATUS_COMMITTED) {
                throw new \LogicException(
                    'Reservasi yang sudah di-commit tidak dapat dilepas.'
                );
            }
    
            $variant = ProductVariant::query()
                ->findOrFail($reservation->product_variant_id);
    
            $product = Product::query()
                ->findOrFail($variant->product_id);
    
            $resolved = $this->resolve($product, (int) $variant->id);
            $inventory = $resolved['inventory'];
            $quantity = (int) $reservation->quantity;
    
            if ((int) $inventory->reserved_quantity < $quantity) {
                throw new \LogicException(
                    'Reserved quantity lebih kecil daripada jumlah reservasi.'
                );
            }
    
            $inventory->decrement('reserved_quantity', $quantity);
    
            $reservation->update([
                'status' => InventoryReservation::STATUS_RELEASED,
                'released_at' => now(),
            ]);
    
            return $reservation->fresh();
        });
    }
    
    /**
     * Commit seluruh reservasi milik order.
     *
     * Hanya reservasi dengan status reserved yang diproses.
     * Pemanggil harus mengunci Order dan menjalankan operasi
     * ini dalam transaksi checkout atau transisi pembayaran.
     */
    public function commitReservationsForOrder(Order $order): void
    {
        DB::transaction(function () use ($order): void {
            $reservations = $order->reservations()
                ->orderBy('product_variant_id')
                ->orderBy('id')
                ->lockForUpdate()
                ->get();
    
            foreach ($reservations as $reservation) {
                if (
                    $reservation->status ===
                    InventoryReservation::STATUS_COMMITTED
                ) {
                    continue;
                }
    
                if (
                    $reservation->status ===
                    InventoryReservation::STATUS_RELEASED
                ) {
                    throw new \LogicException(
                        'Order memiliki reservasi yang sudah dilepas.'
                    );
                }
    
                $this->commitReservation(
                    $reservation->idempotency_key
                );
            }
        });
    }
    
    /**
     * Release seluruh reservasi aktif milik order.
     *
     * Reservasi committed tidak boleh dilepas karena stok fisiknya
     * sudah berkurang. Pemulihannya dilakukan melalui operasi return.
     */
    public function releaseReservationsForOrder(Order $order): void
    {
        DB::transaction(function () use ($order): void {
            $reservations = $order->reservations()
                ->orderBy('product_variant_id')
                ->orderBy('id')
                ->lockForUpdate()
                ->get();
    
            foreach ($reservations as $reservation) {
                if (
                    $reservation->status ===
                    InventoryReservation::STATUS_RELEASED
                ) {
                    continue;
                }
    
                if (
                    $reservation->status ===
                    InventoryReservation::STATUS_COMMITTED
                ) {
                    continue;
                }
    
                if (
                    $reservation->status !==
                    InventoryReservation::STATUS_RESERVED
                ) {
                    throw new \LogicException(
                        'Status reservasi tidak dikenal.'
                    );
                }
    
                $this->releaseReservation(
                    $reservation->idempotency_key
                );
            }
        });
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
    
        if ($quantity < 1) {
            throw ValidationException::withMessages([
                'stock' => 'Jumlah pengurangan stok tidak valid.',
            ]);
        }
    
        $idempotencyKey = sprintf(
            'order:%d:variant:%d:sale',
            $order->id,
            $variant->id
        );
    
        if ($this->hasRecordedMovement(
            $idempotencyKey,
            $variant->id,
            'sale',
            -$quantity,
            Order::class,
            (int) $order->id
        )) {
            return;
        }
    
        $available = (int) $lockedInventory->quantity
            - (int) $lockedInventory->reserved_quantity;
    
        if ($quantity > $available) {
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
            'idempotency_key' => $idempotencyKey,
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
        Order $order,
        ?int $orderItemId = null
    ): void {
        if ($quantity < 1) {
            return;
        }
    
        $resolved = $this->resolve($product, $variantId);
    
        $lockedProduct = $resolved['product'];
        $variant = $resolved['variant'];
        $inventory = $resolved['inventory'];
    
        $idempotencyKey = $orderItemId !== null
            ? sprintf(
                'order:%d:item:%d:return',
                $order->id,
                $orderItemId
            )
            : sprintf(
                'order:%d:variant:%d:return',
                $order->id,
                $variant->id
            );
    
        if ($this->hasRecordedMovement(
            $idempotencyKey,
            $variant->id,
            'return',
            $quantity,
            Order::class,
            (int) $order->id
        )) {
            return;
        }
    
        $inventory->update([
            'quantity' => (int) $inventory->quantity + $quantity,
        ]);
    
        $variant->inventoryMovements()->create([
            'type' => 'return',
            'quantity' => $quantity,
            'reference_type' => Order::class,
            'reference_id' => $order->id,
            'idempotency_key' => $idempotencyKey,
            'note' => 'Stok dikembalikan karena pesanan dibatalkan.',
            'created_by' => auth()->id(),
        ]);
    
        $this->syncLegacyStock($lockedProduct);
    }

    /**
     * Kolom legacy mencerminkan total quantity seluruh varian.
     * Nilai ini untuk kompatibilitas, bukan validasi pembelian.
     */
    public function syncLegacyStock(Product $product): void
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
    
    /**
     * Memulihkan stok seluruh item pesanan.
     *
     * Pemanggil wajib:
     * - berada di dalam DB transaction;
     * - sudah mengunci baris Order dengan lockForUpdate();
     * - hanya memanggil method ini ketika transisi ke canceled sah.
     *
     * Produk dikunci dalam urutan ID menaik agar konsisten
     * di jalur admin, Stripe, dan Midtrans.
     */
    public function restoreItemsForOrder(Order $order): void
    {
        $items = $order->items()
            ->orderBy('product_id')
            ->orderBy('product_variant_id')
            ->orderBy('id')
            ->get();

        $productIds = $items
            ->pluck('product_id')
            ->unique()
            ->sort()
            ->values();

        $products = Product::query()
            ->whereIn('id', $productIds)
            ->orderBy('id')
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        foreach ($items as $item) {
            $product = $products->get($item->product_id);

            if (! $product) {
                throw new \RuntimeException(
                    "Produk {$item->product_id} tidak ditemukan saat memulihkan stok."
                );
            }

            $this->restoreForOrder(
                $product,
                $item->product_variant_id
                    ? (int) $item->product_variant_id
                    : null,
                (int) $item->quantity,
                $order,
                (int) $item->id,
            );
        }
    }
    
    private function hasRecordedMovement(
        string $idempotencyKey,
        int $variantId,
        string $type,
        int $quantity,
        string $referenceType,
        int $referenceId
    ): bool {
        $movement = InventoryMovement::query()
            ->where('idempotency_key', $idempotencyKey)
            ->lockForUpdate()
            ->first();
    
        if (! $movement) {
            return false;
        }
    
        if (
            (int) $movement->product_variant_id !== $variantId
            || $movement->type !== $type
            || (int) $movement->quantity !== $quantity
            || $movement->reference_type !== $referenceType
            || (int) $movement->reference_id !== $referenceId
        ) {
            throw new \LogicException(
                'Inventory movement idempotency key digunakan dengan data yang berbeda.'
            );
        }
    
        return true;
    }
}

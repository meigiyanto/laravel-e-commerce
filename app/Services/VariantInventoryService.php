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
            trim($idempotencyKey) === '' || strlen($idempotencyKey) > 191 || trim($referenceType) === '' || strlen($referenceType) > 100 || $referenceId < 1
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
         * Ambil ulang varian dari database agar tidak bergantung
         * pada data model yang mungkin sudah kedaluwarsa.
         */
            $currentVariant = ProductVariant::query()
            ->whereKey($variant->id)
            ->firstOrFail();

            /*
         * Kunci produk terlebih dahulu. Tenant global scope tetap
         * berlaku pada kedua query ini.
         */
            $product = Product::query()
            ->whereKey($currentVariant->product_id)
            ->lockForUpdate()
            ->firstOrFail();

            /*
         * Ambil ulang varian dengan row lock dan pastikan
         * varian tersebut memang milik produk yang dikunci.
         */
            $lockedVariant = ProductVariant::query()
            ->whereKey($currentVariant->id)
            ->where('product_id', $product->id)
            ->lockForUpdate()
            ->firstOrFail();

            /*
         * Insert reservation menggunakan unique idempotency key.
         * Jika key sudah ada, data lama akan diperiksa di bawah.
         */
            $inserted = DB::table('inventory_reservations')
            ->insertOrIgnore([
                'tenant_id' => $lockedVariant->tenant_id,
                'product_variant_id' => $lockedVariant->id,
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
                /*
             * Key yang sama tidak boleh digunakan untuk payload
             * berbeda, termasuk tenant atau varian berbeda.
             */
                if (
                    (int) $reservation->tenant_id !== (int) $lockedVariant->tenant_id || (int) $reservation->product_variant_id !== (int) $lockedVariant->id || (int) $reservation->quantity !== $quantity || $reservation->reference_type !== $referenceType || (int) $reservation->reference_id !== $referenceId
                ) {
                    throw new \LogicException(
                        'Reservation idempotency key digunakan dengan data berbeda.'
                    );
                }

                /*
             * Hanya reservasi yang masih aktif boleh dikembalikan.
             * Reservasi committed/released tidak boleh diaktifkan ulang.
             */
                if (
                    $reservation->status !== InventoryReservation::STATUS_RESERVED
                ) {
                    throw new \LogicException(
                        'Reservasi dengan idempotency key ini sudah tidak aktif.'
                    );
                }

                return $reservation;
            }

            /*
         * resolve() mengurus inventaris, termasuk backfill stok
         * legacy jika produk belum memiliki inventory.
         */
            $resolved = $this->resolve(
                $product,
                (int) $lockedVariant->id
            );

            $inventory = $resolved['inventory'];

            /*
         * Periksa stok tersedia langsung pada UPDATE agar
         * jumlah reservasi tidak melampaui stok fisik.
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
                (int) $inventory->reserved_quantity < $quantity || (int) $inventory->quantity < $quantity
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
        if ($quantity < 1) {
            throw ValidationException::withMessages([
                'stock' => 'Jumlah pengurangan stok tidak valid.',
            ]);
        }
    
        DB::transaction(function () use (
            $product,
            $variant,
            $inventory,
            $quantity,
            $order
        ): void {
            /*
             * Kunci order terlebih dahulu agar transaksi tidak
             * menggunakan order yang sudah berubah atau berbeda tenant.
             */
            $lockedOrder = Order::query()
                ->whereKey($order->id)
                ->lockForUpdate()
                ->firstOrFail();
    
            /*
             * Ambil produk terbaru dan kunci barisnya.
             * Global scope tenant tetap berlaku.
             */
            $lockedProduct = Product::query()
                ->whereKey($product->id)
                ->lockForUpdate()
                ->firstOrFail();
    
            /*
             * Ambil varian terbaru dan kunci barisnya.
             */
            $lockedVariant = ProductVariant::query()
                ->whereKey($variant->id)
                ->lockForUpdate()
                ->firstOrFail();
    
            /*
             * Pastikan varian memang milik produk yang diberikan.
             */
            if (
                (int) $lockedVariant->product_id
                !== (int) $lockedProduct->id
            ) {
                throw new \LogicException(
                    'Varian tidak dimiliki oleh produk yang diberikan.'
                );
            }
    
            /*
             * Ambil inventaris berdasarkan ID dan kunci barisnya.
             */
            $lockedInventory = Inventory::query()
                ->whereKey($inventory->id)
                ->lockForUpdate()
                ->firstOrFail();
    
            /*
             * Pastikan inventaris memang milik varian tersebut.
             */
            if (
                (int) $lockedInventory->product_variant_id
                !== (int) $lockedVariant->id
            ) {
                throw new \LogicException(
                    'Inventory tidak dimiliki oleh varian yang diberikan.'
                );
            }
    
            /*
             * Semua objek harus berasal dari tenant yang sama.
             * Pada Single Store, seluruh tenant_id harus NULL.
             */
            $sameTenant = static function (
                ?int $left,
                ?int $right
            ): bool {
                return $left === null && $right === null
                    || $left !== null
                        && $right !== null
                        && $left === $right;
            };
    
            $productTenantId = $lockedProduct->tenant_id === null
                ? null
                : (int) $lockedProduct->tenant_id;
    
            $variantTenantId = $lockedVariant->tenant_id === null
                ? null
                : (int) $lockedVariant->tenant_id;
    
            $inventoryTenantId = $lockedInventory->tenant_id === null
                ? null
                : (int) $lockedInventory->tenant_id;
    
            $orderTenantId = $lockedOrder->tenant_id === null
                ? null
                : (int) $lockedOrder->tenant_id;
    
            if (
                ! $sameTenant($productTenantId, $variantTenantId)
                || ! $sameTenant($productTenantId, $inventoryTenantId)
                || ! $sameTenant($productTenantId, $orderTenantId)
            ) {
                throw new \LogicException(
                    'Produk, varian, inventori, dan order harus berasal dari tenant yang sama.'
                );
            }
    
            $idempotencyKey = sprintf(
                'order:%d:variant:%d:sale',
                $lockedOrder->id,
                $lockedVariant->id
            );
    
            /*
             * Pemeriksaan idempotensi tetap dilakukan sebelum stok
             * dikurangi. Pemakaian key dengan data berbeda ditolak.
             */
            if ($this->hasRecordedMovement(
                $idempotencyKey,
                (int) $lockedVariant->id,
                'sale',
                -$quantity,
                Order::class,
                (int) $lockedOrder->id
            )) {
                return;
            }
    
            $available = (int) $lockedInventory->quantity
                - (int) $lockedInventory->reserved_quantity;
    
            if ($quantity > $available) {
                throw ValidationException::withMessages([
                    'stock' => "Stok varian {$lockedVariant->name} tidak mencukupi.",
                ]);
            }
    
            $lockedInventory->update([
                'quantity' => (int) $lockedInventory->quantity - $quantity,
            ]);
    
            $lockedVariant->inventoryMovements()->create([
                'type' => 'sale',
                'quantity' => -$quantity,
                'reference_type' => Order::class,
                'reference_id' => $lockedOrder->id,
                'idempotency_key' => $idempotencyKey,
                'note' => 'Stok dikurangi saat checkout.',
                'created_by' => auth()->id(),
            ]);
    
            $this->syncLegacyStock($lockedProduct);
        });
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
            (int) $movement->product_variant_id !== $variantId || $movement->type !== $type || (int) $movement->quantity !== $quantity || $movement->reference_type !== $referenceType || (int) $movement->reference_id !== $referenceId
        ) {
            throw new \LogicException(
                'Inventory movement idempotency key digunakan dengan data yang berbeda.'
            );
        }

        return true;
    }

    /**
    * Menangani stok saat order dibatalkan berdasarkan status reservasi.
    *
    * reserved  -> release reservasi, tanpa menambah quantity.
    * committed -> pulihkan stok untuk item varian tersebut.
    * released  -> no-op.
    *
    * Item tanpa reservasi tetap dipulihkan untuk mendukung order legacy.
    */
    public function cancelStockForOrder(Order $order): void
    {
        DB::transaction(function () use ($order): void {
            $lockedOrder = Order::query()
            ->whereKey($order->getKey())
            ->lockForUpdate()
            ->firstOrFail();

            $reservations = $lockedOrder->reservations()
            ->orderBy('product_variant_id')
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

            $items = $lockedOrder->items()
            ->orderBy('product_id')
            ->orderBy('product_variant_id')
            ->orderBy('id')
            ->get();

            $productIds = $items->pluck('product_id')
            ->unique()
            ->sort()
            ->values();

            $products = Product::query()
            ->whereIn('id', $productIds)
            ->orderBy('id')
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

            $reservationsByVariant = $reservations->groupBy(
                'product_variant_id'
            );

            $itemsByVariant = $items->groupBy(
                fn ($item) => $item->product_variant_id === null ? 'legacy-'.$item->product_id : (string) $item->product_variant_id
            );

            $handledItemIds = [];

            foreach ($reservationsByVariant as $variantId => $variantReservations) {
                $statuses = $variantReservations->pluck('status')->unique();

                if ($statuses->count() !== 1) {
                    throw new \LogicException(
                        "Status reservasi varian {$variantId} tidak konsisten."
                    );
                }

                $status = $statuses->first();

                $variantItems = $itemsByVariant->get((string) $variantId, collect());

                $reservationQuantity = (int) $variantReservations->sum('quantity');
                $itemQuantity = (int) $variantItems->sum('quantity');

                if ($reservationQuantity !== $itemQuantity) {
                    throw new \LogicException(
                        "Jumlah reservasi varian {$variantId} tidak sesuai dengan item order."
                    );
                }

                if ($status === InventoryReservation::STATUS_RESERVED) {
                    foreach ($variantReservations as $reservation) {
                        $this->releaseReservation($reservation->idempotency_key);
                    }
                } elseif ($status === InventoryReservation::STATUS_COMMITTED) {
                    foreach ($variantItems as $item) {
                        $product = $products->get($item->product_id);

                        if (! $product) {
                            throw new \RuntimeException("Produk {$item->product_id} tidak ditemukan saat memulihkan stok.");
                        }

                        $this->restoreForOrder($product, (int) $variantId, (int) $item->quantity, $lockedOrder, (int) $item->id);

                        $handledItemIds[] = (int) $item->id;
                    }
                } elseif ($status !== InventoryReservation::STATUS_RELEASED) {
                    throw new \LogicException("Status reservasi {$status} tidak dikenal.");
                }
            }

            /*
             * Item tanpa reservasi merupakan order legacy yang stoknya
             * dipotong langsung. Item ini tetap menggunakan mekanisme
             * pemulihan lama yang idempotent.
             */
            foreach ($items as $item) {
                if (in_array((int) $item->id, $handledItemIds, true)) {
                    continue;
                }

                $variantKey = $item->product_variant_id === null ? null : (string) $item->product_variant_id;

                if ($variantKey !== null && $reservationsByVariant->has($variantKey)) {
                    // Varian ini sudah ditangani berdasarkan status reservasinya.
                    continue;
                }

                $product = $products->get($item->product_id);

                if (! $product) {
                    throw new \RuntimeException("Produk {$item->product_id} tidak ditemukan saat memulihkan stok.");
                }

                $this->restoreForOrder($product, $item->product_variant_id ? (int) $item->product_variant_id : null, (int) $item->quantity, $lockedOrder, (int) $item->id);
            }
        });
    }
}
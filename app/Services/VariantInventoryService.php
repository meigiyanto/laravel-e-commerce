<?php

namespace App\Services;

use App\Models\Inventory;
use App\Models\InventoryMovement;
use App\Models\InventoryReservation;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Tenant;
use App\Support\Tenancy\TenantContext;
use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class VariantInventoryService
{
    private function runInOrderTenantContext(
        Order $order,
        Closure $callback
    ): mixed {
        $snapshot = Order::withoutGlobalScope('tenant')
            ->select(['id', 'tenant_id'])
            ->whereKey($order->getKey())
            ->firstOrFail();

        $tenantId = $snapshot->tenant_id !== null
        ? (int) $snapshot->tenant_id
        : null;

        if (config('operational.mode', 'single_store') !== 'multi_tenant') {
            if ($tenantId !== null) {
                throw new \LogicException(
                    'Order single-store tidak boleh terikat pada tenant.'
                );
            }

            return $callback();
        }

        if ($tenantId === null) {
            throw new \LogicException(
                'Tenant untuk operasi inventory tidak ditemukan.'
            );
        }

        $context = app(TenantContext::class);
        $currentTenant = $context->current();

        if ($currentTenant !== null) {
            if ((int) $currentTenant->id !== $tenantId) {
                // Pertahankan perilaku penyembunyian order lintas tenant.
                Order::query()
                    ->whereKey($order->getKey())
                    ->firstOrFail();
            }

            return $callback();
        }

        $tenant = Tenant::query()
            ->whereKey($tenantId)
            ->where('status', 'active')
            ->firstOrFail();

        return $context->run($tenant, $callback);
    }

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

    private function lockReservationContext(string $idempotencyKey): array
    {
        // Snapshot awal hanya untuk mengetahui relasi; belum mengambil row lock.
        $snapshot = InventoryReservation::query()
            ->where('idempotency_key', $idempotencyKey)
            ->firstOrFail();

        $order = null;

        // Order selalu dikunci sebelum Product dan Inventory.
        if ($snapshot->reference_type === Order::class) {
            $order = Order::query()
                ->whereKey($snapshot->reference_id)
                ->lockForUpdate()
                ->firstOrFail();
        }

        $variantSnapshot = ProductVariant::query()
            ->whereKey($snapshot->product_variant_id)
            ->firstOrFail();

        $productSnapshot = Product::query()
            ->whereKey($variantSnapshot->product_id)
            ->firstOrFail();

        // resolve mengunci Product -> seluruh Variant -> Inventory.
        $resolved = $this->resolve(
            $productSnapshot,
            (int) $variantSnapshot->id
        );

        // Reservation baru dikunci setelah Inventory.
        $reservation = InventoryReservation::query()
            ->whereKey($snapshot->id)
            ->lockForUpdate()
            ->firstOrFail();

        $normalize = static fn ($id) => $id === null ? null : (int) $id;
        $productTenant = $normalize($resolved['product']->tenant_id);

        if (
            $normalize($resolved['variant']->tenant_id) !== $productTenant || $normalize($resolved['inventory']->tenant_id) !== $productTenant || $normalize($reservation->tenant_id) !== $productTenant || (
                $order !== null && $normalize($order->tenant_id) !== $productTenant
            ) || (int) $reservation->product_variant_id !== (int) $resolved['variant']->id
        ) {
            throw new \LogicException(
                'Order, reservation, product, variant, dan inventory tidak konsisten.'
            );
        }

        return [
            'order' => $order,
            'reservation' => $reservation,
            'product' => $resolved['product'],
            'variant' => $resolved['variant'],
            'inventory' => $resolved['inventory'],
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
            throw new \InvalidArgumentException('Data reservasi tidak valid.');
        }

        return DB::transaction(function () use (
            $variant,
            $quantity,
            $idempotencyKey,
            $referenceType,
            $referenceId
        ) {
            $variantSnapshot = ProductVariant::query()
                ->whereKey($variant->id)
                ->firstOrFail();

            $order = null;

            if ($referenceType === Order::class) {
                $order = Order::query()
                    ->whereKey($referenceId)
                    ->lockForUpdate()
                    ->firstOrFail();
            }

            $productSnapshot = Product::query()
                ->whereKey($variantSnapshot->product_id)
                ->firstOrFail();

            $resolved = $this->resolve(
                $productSnapshot,
                (int) $variantSnapshot->id
            );

            $product = $resolved['product'];
            $lockedVariant = $resolved['variant'];
            $inventory = $resolved['inventory'];

            $normalize = static fn ($id) => $id === null ? null : (int) $id;

            if (
                $order !== null && $normalize($order->tenant_id) !== $normalize($product->tenant_id)
            ) {
                throw new \LogicException(
                    'Order dan produk reservasi harus berasal dari tenant yang sama.'
                );
            }

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
                if (
                    $normalize($reservation->tenant_id) !== $normalize($lockedVariant->tenant_id) || (int) $reservation->product_variant_id !== (int) $lockedVariant->id || (int) $reservation->quantity !== $quantity || $reservation->reference_type !== $referenceType || (int) $reservation->reference_id !== $referenceId
                ) {
                    throw new \LogicException(
                        'Reservation idempotency key digunakan dengan data berbeda.'
                    );
                }

                if ($reservation->status !== InventoryReservation::STATUS_RESERVED) {
                    throw new \LogicException(
                        'Reservasi dengan idempotency key ini sudah tidak aktif.'
                    );
                }

                return $reservation;
            }

            $updated = Inventory::query()
                ->whereKey($inventory->id)
                ->whereRaw('quantity - reserved_quantity >= ?', [$quantity])
                ->increment('reserved_quantity', $quantity);

            if ($updated !== 1) {
                throw ValidationException::withMessages([
                    'stock' => "Stok varian {$lockedVariant->name} tidak mencukupi.",
                ]);
            }

            return $reservation->fresh();
        });
    }

    public function commitReservation(string $idempotencyKey): InventoryReservation
    {
        return DB::transaction(function () use ($idempotencyKey) {
            $context = $this->lockReservationContext($idempotencyKey);

            $reservation = $context['reservation'];
            $product = $context['product'];
            $variant = $context['variant'];
            $inventory = $context['inventory'];
            $quantity = (int) $reservation->quantity;

            if ($reservation->status === InventoryReservation::STATUS_COMMITTED) {
                return $reservation;
            }

            if ($reservation->status === InventoryReservation::STATUS_RELEASED) {
                throw new \LogicException(
                    'Reservasi yang sudah dilepas tidak dapat di-commit.'
                );
            }

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

    public function releaseReservation(string $idempotencyKey): InventoryReservation
    {
        return DB::transaction(function () use ($idempotencyKey) {
            $context = $this->lockReservationContext($idempotencyKey);

            $reservation = $context['reservation'];
            $inventory = $context['inventory'];
            $quantity = (int) $reservation->quantity;

            if ($reservation->status === InventoryReservation::STATUS_RELEASED) {
                return $reservation;
            }

            if ($reservation->status === InventoryReservation::STATUS_COMMITTED) {
                throw new \LogicException(
                    'Reservasi yang sudah di-commit tidak dapat dilepas.'
                );
            }

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

    public function commitReservationsForOrder(Order $order): void
    {
        DB::transaction(function () use ($order): void {
            $lockedOrder = Order::query()
                ->whereKey($order->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $keys = $lockedOrder->reservations()
                ->orderBy('product_variant_id')
                ->orderBy('id')
                ->pluck('idempotency_key');

            foreach ($keys as $key) {
                $this->commitReservation($key);
            }
        });
    }

    public function releaseReservationsForOrder(Order $order): void
    {
        DB::transaction(function () use ($order): void {
            $lockedOrder = Order::query()
                ->whereKey($order->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $keys = $lockedOrder->reservations()
                ->orderBy('product_variant_id')
                ->orderBy('id')
                ->pluck('idempotency_key');

            foreach ($keys as $key) {
                $this->releaseReservation($key);
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
                (int) $lockedVariant->product_id !== (int) $lockedProduct->id
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
                (int) $lockedInventory->product_variant_id !== (int) $lockedVariant->id
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
                return $left === null && $right === null || $left !== null && $right !== null && $left === $right;
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
                ! $sameTenant($productTenantId, $variantTenantId) || ! $sameTenant($productTenantId, $inventoryTenantId) || ! $sameTenant($productTenantId, $orderTenantId)
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

        DB::transaction(function () use (
            $product,
            $variantId,
            $quantity,
            $order,
            $orderItemId
        ): void {
            // Order harus ditemukan dalam tenant context aktif.
            $lockedOrder = Order::query()
                ->whereKey($order->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $resolved = $this->resolve($product, $variantId);

            $lockedProduct = $resolved['product'];
            $variant = $resolved['variant'];
            $inventory = $resolved['inventory'];

            $normalizeTenantId = static fn ($id) => $id === null
            ? null
            : (int) $id;

            $productTenantId = $normalizeTenantId($lockedProduct->tenant_id);
            $orderTenantId = $normalizeTenantId($lockedOrder->tenant_id);
            $variantTenantId = $normalizeTenantId($variant->tenant_id);
            $inventoryTenantId = $normalizeTenantId($inventory->tenant_id);

            if (
                $productTenantId !== $orderTenantId || $productTenantId !== $variantTenantId || $productTenantId !== $inventoryTenantId
            ) {
                throw new \LogicException(
                    'Produk, varian, inventori, dan order harus berasal dari tenant yang sama.'
                );
            }

            if ($orderItemId !== null) {
                $item = OrderItem::query()
                    ->whereKey($orderItemId)
                    ->where('order_id', $lockedOrder->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if (
                    (int) $item->product_id !== (int) $lockedProduct->id || (int) $item->quantity !== $quantity || (
                        $item->product_variant_id !== null && (int) $item->product_variant_id !== (int) $variant->id
                    )
                ) {
                    throw new \LogicException(
                        'Order item tidak sesuai dengan produk, varian, atau jumlah pemulihan.'
                    );
                }
            }

            $idempotencyKey = $orderItemId !== null
            ? sprintf(
                'order:%d:item:%d:return',
                $lockedOrder->id,
                $orderItemId
            )
            : sprintf(
                'order:%d:variant:%d:return',
                $lockedOrder->id,
                $variant->id
            );

            if ($this->hasRecordedMovement(
                $idempotencyKey,
                (int) $variant->id,
                'return',
                $quantity,
                Order::class,
                (int) $lockedOrder->id
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
                'reference_id' => $lockedOrder->id,
                'idempotency_key' => $idempotencyKey,
                'note' => 'Stok dikembalikan karena pesanan dibatalkan.',
                'created_by' => auth()->id(),
            ]);

            $this->syncLegacyStock($lockedProduct);
        });
    }

    /**
     * Kolom legacy mencerminkan total quantity seluruh varian.
     * Nilai ini untuk kompatibilitas, bukan validasi pembelian.
     */
    public function syncLegacyStock(Product $product): void
    {
        $lockedProduct = Product::query()
            ->whereKey($product->getKey())
            ->lockForUpdate()
            ->firstOrFail();

        $totalQuantity = Inventory::query()
            ->whereHas('variant',
                function ($query) use ($lockedProduct) {
                    $query->where('product_id', $lockedProduct->id);
                })
            ->sum('quantity');

        $lockedProduct->update([
            'stock' => (int) $totalQuantity,
        ]);
    }

    public function restoreItemsForOrder(Order $order): void
    {
        DB::transaction(function () use ($order): void {
            $lockedOrder = Order::query()
                ->whereKey($order->getKey())
                ->lockForUpdate()
                ->firstOrFail();

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
                    $lockedOrder,
                    (int) $item->id
                );
            }
        });
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
            ->where('idempotency_key',
                $idempotencyKey)
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
        $this->runInOrderTenantContext($order, function () use ($order): void {
            DB::transaction(function () use ($order): void {
                $lockedOrder = Order::query()
                    ->whereKey($order->getKey())
                    ->lockForUpdate()
                    ->firstOrFail();

                $reservations = $lockedOrder->reservations()
                    ->orderBy('product_variant_id')
                    ->orderBy('id')
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

                $variants = ProductVariant::query()
                    ->whereIn(
                        'id',
                        $items->pluck('product_variant_id')->filter()->unique()
                    )
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('id');

                foreach ($items as $item) {
                    $product = $products->get($item->product_id);

                    if (! $product) {
                        throw new \RuntimeException(
                            "Produk {$item->product_id} tidak ditemukan saat membatalkan order."
                        );
                    }

                    if ($item->quantity < 1) {
                        throw new \LogicException(
                            "Jumlah item order {$item->id} tidak valid."
                        );
                    }

                    if ($item->product_variant_id === null) {
                        continue;
                    }

                    $variant = $variants->get($item->product_variant_id);

                    if (
                        ! $variant || (int) $variant->product_id !== (int) $product->id
                    ) {
                        throw new \LogicException(
                            "Varian pada order item {$item->id} tidak dimiliki oleh produk tersebut."
                        );
                    }
                }

                $reservationsByVariant = $reservations->groupBy(
                    'product_variant_id'
                );

                $itemsByVariant = $items->groupBy(
                    fn ($item) => $item->product_variant_id === null
                    ? 'legacy-'.$item->product_id
                    : (string) $item->product_variant_id
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
                    $variantItems = $itemsByVariant->get(
                        (string) $variantId,
                        collect()
                    );

                    $reservationQuantity = (int) $variantReservations->sum('quantity');
                    $itemQuantity = (int) $variantItems->sum('quantity');

                    if ($reservationQuantity !== $itemQuantity) {
                        throw new \LogicException(
                            "Jumlah reservasi varian {$variantId} tidak sesuai dengan item order."
                        );
                    }

                    if ($status === InventoryReservation::STATUS_RESERVED) {
                        foreach ($variantReservations as $reservation) {
                            $this->releaseReservation(
                                $reservation->idempotency_key
                            );
                        }
                    } elseif ($status === InventoryReservation::STATUS_COMMITTED) {
                        foreach ($variantItems as $item) {
                            $product = $products->get($item->product_id);

                            if (! $product) {
                                throw new \RuntimeException(
                                    "Produk {$item->product_id} tidak ditemukan saat memulihkan stok."
                                );
                            }

                            $this->restoreForOrder(
                                $product,
                                (int) $variantId,
                                (int) $item->quantity,
                                $lockedOrder,
                                (int) $item->id
                            );

                            $handledItemIds[] = (int) $item->id;
                        }
                    } elseif ($status !== InventoryReservation::STATUS_RELEASED) {
                        throw new \LogicException(
                            "Status reservasi {$status} tidak dikenal."
                        );
                    }
                }

                foreach ($items as $item) {
                    if (in_array((int) $item->id, $handledItemIds, true)) {
                        continue;
                    }

                    $variantKey = $item->product_variant_id === null
                    ? null
                    : (string) $item->product_variant_id;

                    if (
                        $variantKey !== null && $reservationsByVariant->has($variantKey)
                    ) {
                        continue;
                    }

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
                        $lockedOrder,
                        (int) $item->id
                    );
                }
            });
        }
        );
    }
}

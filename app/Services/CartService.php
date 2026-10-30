<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\Inventory;
use Illuminate\Support\Facades\DB;
use App\Models\ProductVariant;
use App\Services\VariantInventoryService;
use Illuminate\Validation\ValidationException;

class CartService
{
    /**
     * Ambil cart milik user yang sedang login.
     */
    public function getUserCart(int $userId): Cart
    {
        return Cart::firstOrCreate([
            'user_id' => $userId,
        ]);
    }

    /**
     * Ambil cart user beserta produk.
     */
    public function getUserCartWithItems(int $userId): Cart
    {
        $cart = $this->getUserCart($userId);
        
        return $cart->load([
            'items.product.category',
            'items.product.subCategory',
            'items.variant.inventory',
            'items.variant.attributeValues.attribute',
        ]);
    }

    /**
     * Ambil cart guest dari session.
     *
     * Format:
     * [
     *     product_id => quantity,
     * ]
     */
    public function getGuestCart(): array
    {
        $cart = session()->get('cart', []);
        $normalized = [];
        $inventoryService = app(VariantInventoryService::class);
        
        foreach ($cart as $key => $item) {
            // Pertahankan format varian yang sudah benar.
            if (
                is_array($item)
                && isset($item['product_variant_id'], $item['product_id'])
            ) {
                $variantId = (int) $item['product_variant_id'];
                $productId = (int) $item['product_id'];
                $quantity = (int) ($item['quantity'] ?? 0);
    
                if ($variantId > 0 && $productId > 0 && $quantity > 0) {
                    // A session entry must reference a variant belonging to
                    // the same product. Otherwise totals and cart rendering
                    // can disagree about which product is being purchased.
                    $variant = ProductVariant::query()
                        ->whereKey($variantId)
                        ->where('product_id', $productId)
                        ->first();

                    if ($variant) {
                        $variantKey = 'variant_'.$variantId;
                        $normalized[$variantKey] = [
                            'product_id' => $productId,
                            'product_variant_id' => $variantId,
                            // Canonicalize aliases to one key without losing
                            // quantity if a legacy/malformed session repeats it.
                            'quantity' => $quantity
                                + (int) ($normalized[$variantKey]['quantity'] ?? 0),
                        ];
                    }
                }
    
                continue;
            }
    
            // Migrasikan format lama: product_id => quantity.
            $productId = is_numeric((string) $key) ? (int) $key : 0;
    
            if ($productId < 1) {
                continue;
            }
    
            $product = Product::find($productId);
    
            if (! $product) {
                continue;
            }
    
            $quantity = is_array($item) ? (int) ($item['quantity'] ?? 0) : (int) $item;
    
            if ($quantity < 1) {
                continue;
            }
    
            $resolved = $inventoryService->resolve($product);
            $variant = $resolved['variant'];
    
            $variantKey = 'variant_'.$variant->id;
            $normalized[$variantKey] = [
                'product_id' => $product->id,
                'product_variant_id' => $variant->id,
                'quantity' => $quantity
                    + (int) ($normalized[$variantKey]['quantity'] ?? 0),
            ];
        }
        
        if ($cart !== $normalized) {
            session()->put('cart', $normalized);
        }

        foreach ($normalized as $variantKey => $item) {
            $variant = ProductVariant::query()
                ->with('inventory')
                ->whereKey($item['product_variant_id'])
                ->where('product_id', $item['product_id'])
                ->first();

            $inventory = $variant?->inventory;

            if (
                ! $variant
                || $variant->status !== 'active'
                || ! $inventory
            ) {
                unset($normalized[$variantKey]);
                continue;
            }

            $available = max(
                0,
                (int) $inventory->quantity
                    - (int) $inventory->reserved_quantity
            );

            if ($available < 1) {
                unset($normalized[$variantKey]);
                continue;
            }

            $normalized[$variantKey]['quantity'] = min(
                (int) $item['quantity'],
                $available
            );
        }

        if ($cart !== $normalized) {
            session()->put('cart', $normalized);
        }
    
        return $normalized;
    }

    /**
     * Tambahkan produk ke cart.
     *
     * Guest:
     *   disimpan di session.
     *
     * User:
     *   disimpan di database.
     */
    public function add(
        Product $product,
        int $quantity = 1,
        ?int $variantId = null
    ): void {
        if ($quantity < 1) {
            throw new \RuntimeException(
                'Jumlah produk minimal 1.'
            );
        }
    
        DB::transaction(function () use (
            $product,
            $quantity,
            $variantId
        ) {
            $resolved = app(VariantInventoryService::class)
                ->resolve($product, $variantId);
    
            $variant = $resolved['variant'];
            $inventory = $resolved['inventory'];
    
            if ($variant->status !== 'active') {
                throw new \RuntimeException(
                    'Varian ini tidak tersedia.'
                );
            }
    
            $available = (int) $inventory->quantity
                - (int) $inventory->reserved_quantity;
    
            if ($available < 1) {
                throw new \RuntimeException(
                    'Varian ini sedang habis.'
                );
            }
    
            if (auth()->check()) {
                $cart = $this->getUserCart(auth()->id());
    
                $item = CartItem::query()
                    ->where('cart_id', $cart->id)
                    ->where('product_variant_id', $variant->id)
                    ->lockForUpdate()
                    ->first();
    
                // Item lama tanpa variant_id hanya dapat dipetakan
                // otomatis jika produk ini memiliki varian tersebut.
                if (! $item && $variant->is_default) {
                    $item = CartItem::query()
                        ->where('cart_id', $cart->id)
                        ->where('product_id', $product->id)
                        ->whereNull('product_variant_id')
                        ->lockForUpdate()
                        ->first();
    
                    if ($item) {
                        $item->update([
                            'product_variant_id' => $variant->id,
                        ]);
                    }
                }
    
                $newQuantity = $quantity + ($item?->quantity ?? 0);
    
                if ($newQuantity > $available) {
                    throw new \RuntimeException(
                        'Jumlah melebihi stok varian yang tersedia.'
                    );
                }
    
                if ($item) {
                    $item->update([
                        'quantity' => $newQuantity,
                    ]);
                } else {
                    CartItem::create([
                        'cart_id' => $cart->id,
                        'product_id' => $product->id,
                        'product_variant_id' => $variant->id,
                        'quantity' => $quantity,
                    ]);
                }
    
                return;
            }
    
            $cart = $this->getGuestCart();
            $key = 'variant_'.$variant->id;
    
            $currentQuantity = (int) (
                $cart[$key]['quantity'] ?? 0
            );
    
            $newQuantity = $currentQuantity + $quantity;
    
            if ($newQuantity > $available) {
                throw new \RuntimeException(
                    'Jumlah melebihi stok varian yang tersedia.'
                );
            }
    
            $cart[$key] = [
                'product_id' => $product->id,
                'product_variant_id' => $variant->id,
                'quantity' => $newQuantity,
            ];
    
            session()->put('cart', $cart);
        });
    }

    /**
     * Tambahkan produk ke cart user.
     */
    protected function addToUserCart(
        int $userId,
        Product $product,
        int $quantity
    ): void {
        $cart = $this->getUserCart($userId);

        $cartItem = CartItem::where('cart_id', $cart->id)
            ->where('product_id', $product->id)
            ->first();

        $newQuantity = $quantity;

        if ($cartItem) {
            $newQuantity += $cartItem->quantity;
        }

        if ($newQuantity > $product->stock) {
            throw new \RuntimeException(
                'Jumlah produk melebihi stok yang tersedia.'
            );
        }

        if ($cartItem) {
            $cartItem->update([
                'quantity' => $newQuantity,
            ]);

            return;
        }

        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => $quantity,
        ]);
    }

    /**
     * Tambahkan produk ke cart guest.
     */
    protected function addToGuestCart(
        Product $product,
        int $quantity
    ): void {
        $cart = $this->getGuestCart();

        $productId = (string) $product->id;

        $currentQuantity = (int) ($cart[$productId] ?? 0);

        $newQuantity = $currentQuantity + $quantity;

        if ($newQuantity > $product->stock) {
            throw new \RuntimeException(
                'Jumlah produk melebihi stok yang tersedia.'
            );
        }

        $cart[$productId] = $newQuantity;

        session()->put('cart', $cart);
    }

    /**
     * Update item keranjang.
     * Authenticated menggunakan CartItem.id.
     * Guest menggunakan ProductVariant.id.
     */
    public function update(int $itemId, int $quantity): void
    {
        if ($quantity < 1) {
            throw new \RuntimeException('Jumlah produk minimal 1.');
        }
    
        if (auth()->check()) {
            $this->updateUserCartItem($itemId, (int) auth()->id(), $quantity);
            return;
        }
    
        $this->updateGuestCartVariant($itemId, $quantity);
    }
    
    /**
     * Update item database berdasarkan CartItem.id dan pemilik cart.
     */
    public function updateUserCartItem(int $cartItemId, int $userId, int $quantity): void
    {
        if ($quantity < 1) {
            throw new \RuntimeException('Jumlah produk minimal 1.');
        }
    
        DB::transaction(function () use ($cartItemId, $userId, $quantity) {
            $cart = Cart::query()
                ->where('user_id', $userId)
                ->lockForUpdate()
                ->firstOrFail();
    
            $item = CartItem::query()
                ->whereKey($cartItemId)
                ->where('cart_id', $cart->id)
                ->lockForUpdate()
                ->firstOrFail();
    
            if ($item->product_variant_id) {
                $variant = ProductVariant::query()
                    ->whereKey($item->product_variant_id)
                    ->where('product_id', $item->product_id)
                    ->lockForUpdate()
                    ->firstOrFail();
            } else {
                // Kompatibilitas item lama tanpa product_variant_id.
                $resolved = app(VariantInventoryService::class)
                    ->resolve($item->product);
    
                $variant = $resolved['variant'];
    
                $item->update([
                    'product_variant_id' => $variant->id,
                ]);
            }
    
            $inventory = Inventory::query()
                ->where('product_variant_id', $variant->id)
                ->lockForUpdate()
                ->first();
    
            $this->assertVariantCanContainQuantity($variant, $inventory, $quantity);
    
            $item->update([
                'quantity' => $quantity,
            ]);
        });
    }
    
    /**
     * Update guest cart berdasarkan ProductVariant.id.
     */
    public function updateGuestCartVariant(int $variantId, int $quantity): void
    {
        if ($quantity < 1) {
            throw new \RuntimeException('Jumlah produk minimal 1.');
        }
    
        $cart = $this->getGuestCart();
        $key = 'variant_'.$variantId;
    
        if (! isset($cart[$key])) {
            throw new \RuntimeException('Varian tidak ditemukan di keranjang.');
        }
    
        DB::transaction(function () use (&$cart, $key, $variantId, $quantity) {
            $item = $cart[$key];
    
            $variant = ProductVariant::query()
                ->whereKey($variantId)
                ->where('product_id', (int) $item['product_id'])
                ->lockForUpdate()
                ->firstOrFail();
    
            $inventory = Inventory::query()
                ->where('product_variant_id', $variant->id)
                ->lockForUpdate()
                ->first();
    
            $this->assertVariantCanContainQuantity($variant, $inventory, $quantity);
    
            $cart[$key]['quantity'] = $quantity;
        });
    
        session()->put('cart', $cart);
    }
    
    /**
     * Validasi status varian dan stok tersedia.
     */
    private function assertVariantCanContainQuantity(
        ProductVariant $variant,
        ?Inventory $inventory,
        int $quantity
    ): void {
        if ($variant->status !== 'active') {
            throw new \RuntimeException('Varian ini tidak tersedia.');
        }
    
        if (! $inventory) {
            throw new \RuntimeException('Stok varian tidak ditemukan.');
        }
    
        $available = max(
            0,
            (int) $inventory->quantity - (int) $inventory->reserved_quantity
        );
    
        if ($quantity > $available) {
            throw new \RuntimeException(
                'Jumlah melebihi stok varian yang tersedia.'
            );
        }
    }

    /**
     * Update cart guest.
     */
    protected function updateGuestCart(
        Product $product,
        int $quantity
    ): void {
        $cart = $this->getGuestCart();

        $productId = (string) $product->id;

        if (!array_key_exists($productId, $cart)) {
            throw new \RuntimeException(
                'Produk tidak ditemukan di keranjang.'
            );
        }

        $cart[$productId] = $quantity;

        session()->put('cart', $cart);
    }

    /**
     * Hapus produk dari cart.
     */
    public function remove(int $productId): void
    {
        if (auth()->check()) {
            $cart = $this->getUserCart(auth()->id());
    
            $cartItem = CartItem::where('cart_id', $cart->id)
                ->where('product_id', $productId)
                ->first();
    
            if ($cartItem) {
                $cartItem->delete();
            }
    
            return;
        }
    
        $cart = $this->getGuestCart();
        $key = 'variant_'.$productId;
    
        if (! isset($cart[$key])) {
            $matches = [];
    
            foreach ($cart as $cartKey => $item) {
                if ((int) ($item['product_id'] ?? 0) === $productId) {
                    $matches[] = $cartKey;
                }
            }
    
            // Jangan menghapus varian yang salah jika produk punya
            // lebih dari satu varian di keranjang.
            if (count($matches) === 1) {
                $key = $matches[0];
            }
        }
    
        unset($cart[$key]);
    
        session()->put('cart', $cart);
    }

    /**
     * Hitung jumlah seluruh item dalam cart.
     */
    public function count(): int
    {
        if (auth()->check()) {
            return (int) CartItem::whereHas('cart', function ($query) {
                $query->where('user_id', auth()->id());
            })->sum('quantity');
        }
    
        return collect($this->getGuestCart())->sum(function ($item) {
            // Format cart guest baru: item berisi data varian dan quantity.
            if (is_array($item)) {
                return (int) ($item['quantity'] ?? 0);
            }
    
            // Kompatibilitas dengan format lama: product_id => quantity.
            return (int) $item;
        });
    }

    /**
     * Hitung subtotal cart.
     */
    public function total(): float
    {
        if (auth()->check()) {
            $cart = $this->getUserCartWithItems(auth()->id());
    
            return (float) $cart->items->sum(function ($item) {
                $price = $item->variant?->price
                    ?? $item->product->price;
    
                return (float) $price * $item->quantity;
            });
        }
    
        $cart = $this->getGuestCart();
    
        if (empty($cart)) {
            return 0.0;
        }
    
        $total = 0.0;
    
        foreach ($cart as $key => $item) {
            // Format baru: variant_{id} => data item.
            if (is_array($item)) {
                $variantId = $item['product_variant_id'] ?? null;
                $productId = $item['product_id'] ?? null;
                $quantity = (int) ($item['quantity'] ?? 0);
    
                if ($quantity < 1) {
                    continue;
                }
    
                $variant = $variantId
                    ? ProductVariant::with('product')->find($variantId)
                    : null;
    
                $product = $variant?->product
                    ?? ($productId ? Product::find($productId) : null);
    
                if ($product) {
                    $price = $variant?->price ?? $product->price;
                    $total += (float) $price * $quantity;
                }
    
                continue;
            }
    
            // Format lama: product_id => quantity.
            $product = Product::find($key);
            $quantity = (int) $item;
    
            if ($product && $quantity > 0) {
                $total += (float) $product->price * $quantity;
            }
        }
    
        return $total;
    }

    /**
     * Gabungkan cart guest ke cart user setelah login.
     *
     * Contoh:
     *
     * Guest:
     *   Product A = 2
     *   Product B = 3
     *
     * User:
     *   Product A = 1
     *
     * Hasil:
     *   Product A = 3
     *   Product B = 3
     */
    public function mergeGuestCartIntoUserCart(int $userId): void
    {
        $guestCart = $this->getGuestCart();
    
        if (empty($guestCart)) {
            return;
        }
    
        DB::transaction(function () use ($userId, $guestCart) {
            $cart = $this->getUserCart($userId);
    
            foreach ($guestCart as $item) {
                if (! is_array($item)) {
                    continue;
                }
    
                $productId = (int) ($item['product_id'] ?? 0);
                $variantId = (int) ($item['product_variant_id'] ?? 0);
                $quantity = (int) ($item['quantity'] ?? 0);
    
                if ($productId < 1 || $quantity < 1) {
                    continue;
                }
    
                $product = Product::find($productId);
    
                if (! $product) {
                    continue;
                }
    
                $variant = $variantId > 0
                    ? ProductVariant::with('inventory')->find($variantId)
                    : null;
    
                if (! $variant || (int) $variant->product_id !== $product->id) {
                    $resolved = app(VariantInventoryService::class)
                        ->resolve($product);
    
                    $variant = $resolved['variant'];
                    $variant->loadMissing('inventory');
                }
    
                if ($variant->status !== 'active' || ! $variant->inventory) {
                    continue;
                }
    
                $inventory = $variant->inventory;
                $available = max(
                    0,
                    (int) $inventory->quantity
                        - (int) $inventory->reserved_quantity
                );
    
                if ($available < 1) {
                    continue;
                }
    
                $cartItem = CartItem::where('cart_id', $cart->id)
                    ->where('product_variant_id', $variant->id)
                    ->lockForUpdate()
                    ->first();
    
                // Kompatibilitas dengan item lama tanpa product_variant_id.
                if (! $cartItem && $variant->is_default) {
                    $cartItem = CartItem::where('cart_id', $cart->id)
                        ->where('product_id', $product->id)
                        ->whereNull('product_variant_id')
                        ->lockForUpdate()
                        ->first();
    
                    if ($cartItem) {
                        $cartItem->update([
                            'product_variant_id' => $variant->id,
                        ]);
                    }
                }
    
                $existingQuantity = (int) ($cartItem?->quantity ?? 0);
                $newQuantity = min(
                    $existingQuantity + $quantity,
                    $available
                );
    
                if ($newQuantity < 1) {
                    continue;
                }
    
                if ($cartItem) {
                    $cartItem->update(['quantity' => $newQuantity]);
                } else {
                    CartItem::create([
                        'cart_id' => $cart->id,
                        'product_id' => $product->id,
                        'product_variant_id' => $variant->id,
                        'quantity' => $newQuantity,
                    ]);
                }
            }
        });
    
        session()->forget('cart');
    }


    /**
     * Bersihkan cart guest.
     */
    public function clearGuestCart(): void
    {
        session()->forget('cart');
    }

    /**
     * Apakah cart guest kosong?
     */
    public function isGuestCartEmpty(): bool
    {
        return empty($this->getGuestCart());
    }

    /**
     * Apakah cart user kosong?
     */
    public function isUserCartEmpty(int $userId): bool
    {
        $cart = Cart::where('user_id', $userId)->first();

        if (!$cart) {
            return true;
        }

        return !$cart->items()->exists();
    }
}

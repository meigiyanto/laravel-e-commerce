<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
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
        return session()->get('cart', []);
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
     * Update quantity cart user atau guest.
     */
    public function update(int $productId, int $quantity): void
    {
        if ($quantity < 1) {
            throw new \RuntimeException(
                'Jumlah produk minimal 1.'
            );
        }

        $product = Product::findOrFail($productId);

        if ($quantity > $product->stock) {
            throw new \RuntimeException(
                'Jumlah produk melebihi stok yang tersedia.'
            );
        }

        if (auth()->check()) {
            $this->updateUserCart(
                auth()->id(),
                $product,
                $quantity
            );

            return;
        }

        $this->updateGuestCart(
            $product,
            $quantity
        );
    }

    /**
     * Update cart user.
     */
    protected function updateUserCart(
        int $userId,
        Product $product,
        int $quantity
    ): void {
        $cart = $this->getUserCart($userId);

        $cartItem = CartItem::where('cart_id', $cart->id)
            ->where('product_id', $product->id)
            ->first();

        if (!$cartItem) {
            throw new \RuntimeException(
                'Produk tidak ditemukan di keranjang.'
            );
        }

        $cartItem->update([
            'quantity' => $quantity,
        ]);
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

        unset($cart[(string) $productId]);

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
                return $item->product->price * $item->quantity;
            });
        }

        $cart = $this->getGuestCart();

        if (empty($cart)) {
            return 0;
        }

        $products = Product::whereIn(
            'id',
            array_keys($cart)
        )->get();

        return (float) $products->sum(function ($product) use ($cart) {
            $quantity = (int) (
                $cart[(string) $product->id]
                ?? $cart[$product->id]
                ?? 0
            );

            return $product->price * $quantity;
        });
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

            foreach ($guestCart as $productId => $quantity) {
                $product = Product::find($productId);

                if (!$product) {
                    continue;
                }

                $quantity = (int) $quantity;

                if ($quantity < 1 || $product->stock < 1) {
                    continue;
                }

                $cartItem = CartItem::where('cart_id', $cart->id)
                    ->where('product_id', $product->id)
                    ->first();

                $existingQuantity = $cartItem
                    ? $cartItem->quantity
                    : 0;

                $newQuantity = $existingQuantity + $quantity;

                /*
                 * Jangan sampai hasil merge melebihi stok.
                 */
                $newQuantity = min(
                    $newQuantity,
                    $product->stock
                );

                if ($newQuantity < 1) {
                    continue;
                }

                if ($cartItem) {
                    $cartItem->update([
                        'quantity' => $newQuantity,
                    ]);
                } else {
                    CartItem::create([
                        'cart_id' => $cart->id,
                        'product_id' => $product->id,
                        'quantity' => $newQuantity,
                    ]);
                }
            }
        });

        /*
         * Cart guest sudah berhasil digabungkan
         * ke cart user, sehingga session cart dapat
         * dibersihkan.
         */
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

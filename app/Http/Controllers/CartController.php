<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\CartItem;
use App\Services\CartService;
use App\Models\ProductVariant;
use Illuminate\Support\Collection;

use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CartController extends Controller
{
    public function __construct(
        protected CartService $cartService
    ) {
    }

    /**
     * Display the shopping cart.
     */
    public function index()
    {
        if (auth()->check()) {
            $cart = $this->cartService->getUserCartWithItems(
                auth()->id()
            );

            $total = $this->cartService->total();
        } else {
            $items = $this->buildGuestCartItems();
            $cart = (object) [
                'id' => null,
                'user_id' => null,
                'items' => $items,
            ];
        
            $total = $this->cartService->total();
        }

        return view('storefront.cart', compact(
            'cart',
            'total'
        ));
    }

    /**
     * Return shopping cart drawer content.
     */
    public function drawer()
    {
        $mobileCartCount = $this->cartService->count();
    
        if (auth()->check()) {
            $cart = $this->cartService->getUserCartWithItems(
                auth()->id()
            );
    
            $mobileCartItems = $cart->items;
        } else {
            $mobileCartItems = $this->buildGuestCartItems();
        }
    
        $mobileCartTotal = $mobileCartItems->sum(function ($item) {
            $price = $item->variant?->price
                ?? $item->product->price;
    
            return (float) $price * (int) $item->quantity;
        });
    
        return response()->json([
            'success' => true,
            'cart_count' => $mobileCartCount,
            'html' => view(
                'layouts.partials.storefront.mobile-cart-content',
                compact(
                    'mobileCartItems',
                    'mobileCartCount',
                    'mobileCartTotal'
                )
            )->render(),
        ]);
    }

    /**
     * Add a product to the shopping cart.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_id' => [
                'required',
                'integer',
                'exists:products,id',
            ],
            'quantity' => [
                'required',
                'integer',
                'min:1',
            ],
            'product_variant_id' => [
                'nullable',
                'integer',
                'exists:product_variants,id',
            ],
        ]);

        $product = Product::findOrFail(
            $validated['product_id']
        );

        try {
            
            $this->cartService->add(
                $product,
                (int) $validated['quantity'],
                isset($validated['product_variant_id'])
                    ? (int) $validated['product_variant_id']
                    : null
            );
        } catch (\RuntimeException $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }

            return back()->with(
                'error',
                $e->getMessage()
            );
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Produk berhasil ditambahkan ke keranjang.',
                'cart_count' => $this->cartService->count(),
            ]);
        }

        return redirect()
            ->route('cart.index')
            ->with(
                'success',
                'Produk berhasil ditambahkan ke keranjang.'
            );
    }

    /**
     * Update product quantity in the shopping cart.
     */
    public function update(Request $request, int $cartItem)
    {
        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:1'],
        ]);
        
        if (! auth()->check()) {
            try {
                $this->cartService->update(
                    $cartItem,
                    (int) $validated['quantity']
                );
            } catch (\RuntimeException $e) {
                throw ValidationException::withMessages([
                    'quantity' => $e->getMessage(),
                ]);
            }
        
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'cart_count' => $this->cartService->count(),
                    'total' => $this->cartService->total(),
                ]);
            }
        
            return back()->with('success', 'Keranjang berhasil diperbarui.');
        }
    
        $item = CartItem::query()
            ->whereKey($cartItem)
            ->whereHas('cart', function ($query) {
                $query->where('user_id', auth()->id());
            })
            ->with(['product', 'variant.inventory'])
            ->firstOrFail();
    
        $inventory = $item->variant?->inventory;
        $available = $inventory ? (int) $inventory->quantity - (int) $inventory->reserved_quantity : (int) $item->product->stock;
    
        if ($validated['quantity'] > $available) {
            throw ValidationException::withMessages([
                'quantity' => 'Jumlah melebihi stok yang tersedia.',
            ]);
        }
    
        $item->update(['quantity' => $validated['quantity']]);
    
        $total = $this->cartService->total();
        $cartCount = $this->cartService->count();
        
        if ($request->expectsJson()) {
            $variant = ProductVariant::with(['inventory', 'product'])
                ->findOrFail($cartItem);
        
            $quantity = (int) $validated['quantity'];
            $price = (float) ($variant->price ?? $variant->product->price);
        
            return response()->json([
                'success' => true,
                'message' => 'Jumlah produk berhasil diperbarui.',
                'variant_id' => $variant->id,
                'quantity' => $quantity,
                'subtotal' => $price * $quantity,
                'total' => $this->cartService->total(),
                'cart_count' => $this->cartService->count(),
                'item_count' => $this->cartService->count(),
            ]);
        }
    
        return back()->with('success', 'Keranjang berhasil diperbarui.');
    }

    /**
     * Remove a product from the shopping cart.
     */
    public function destroy(Request $request, int $cartItem)
    {
        if (! auth()->check()) {
            $this->cartService->remove($cartItem);
        
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'cart_count' => $this->cartService->count(),
                    'total' => $this->cartService->total(),
                ]);
            }
        
            return back()->with('success', 'Produk berhasil dihapus dari keranjang.');
        }
        
        $item = CartItem::query()
            ->whereKey($cartItem)
            ->whereHas('cart', function ($query) {
                $query->where('user_id', auth()->id());
            })
            ->firstOrFail();
    
        $item->delete();
    
        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Produk berhasil dihapus dari keranjang.',
                'cart_count' => $this->cartService->count(),
            ]);
        }
    
        return back()->with('success', 'Produk berhasil dihapus dari keranjang.');
    }
    
    private function buildGuestCartItems(): Collection
    {
        $guestCart = $this->cartService->getGuestCart();
    
        $variantIds = collect($guestCart)
            ->pluck('product_variant_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();
    
        $variants = ProductVariant::with([
            'product.category',
            'product.subCategory',
            'inventory',
            'attributeValues.attribute',
        ])->whereIn('id', $variantIds)->get()->keyBy('id');
    
        return collect($guestCart)->map(function ($entry) use ($variants) {
            $variant = $variants->get(
                (int) ($entry['product_variant_id'] ?? 0)
            );
    
            if (! $variant || ! $variant->product) {
                return null;
            }
    
            return (object) [
                'id' => null,
                'product_id' => $variant->product_id,
                'product_variant_id' => $variant->id,
                'quantity' => (int) $entry['quantity'],
                'product' => $variant->product,
                'variant' => $variant,
            ];
        })->filter()->values();
    }

}

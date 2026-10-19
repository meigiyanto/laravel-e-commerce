<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\CartItem;
use App\Services\CartService;
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
            $guestCart = $this->cartService->getGuestCart();

            $productIds = array_keys($guestCart);

            $products = Product::with([
                'category',
                'subCategory',
            ])
                ->whereIn('id', $productIds)
                ->get();

            $items = $products->map(function ($product) use ($guestCart) {
                $quantity = (int) (
                    $guestCart[(string) $product->id]
                    ?? $guestCart[$product->id]
                    ?? 0
                );

                return (object) [
                    'id' => null,
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'product' => $product,
                ];
            });

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
            $guestCart = $this->cartService->getGuestCart();

            $productIds = array_keys($guestCart);

            $products = empty($productIds)
                ? collect()
                : Product::with([
                    'category',
                    'subCategory',
                ])
                    ->whereIn('id', $productIds)
                    ->get();

            $mobileCartItems = $products
                ->map(function ($product) use ($guestCart) {
                    $quantity = (int) (
                        $guestCart[(string) $product->id]
                        ?? $guestCart[$product->id]
                        ?? 0
                    );

                    return (object) [
                        'id' => null,
                        'product_id' => $product->id,
                        'quantity' => $quantity,
                        'product' => $product,
                    ];
                })
                ->filter(function ($item) {
                    return $item->quantity > 0;
                });
        }

        $mobileCartTotal = $mobileCartItems->sum(function ($item) {
            return $item->product->price * $item->quantity;
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
    
        $item = CartItem::query()
            ->whereKey($cartItem)
            ->whereHas('cart', function ($query) {
                $query->where('user_id', auth()->id());
            })
            ->with(['product', 'variant.inventory'])
            ->firstOrFail();
    
        $inventory = $item->variant?->inventory;
        $available = $inventory
            ? (int) $inventory->quantity - (int) $inventory->reserved_quantity
            : (int) $item->product->stock;
    
        if ($validated['quantity'] > $available) {
            throw ValidationException::withMessages([
                'quantity' => 'Jumlah melebihi stok yang tersedia.',
            ]);
        }
    
        $item->update(['quantity' => $validated['quantity']]);
    
        $total = $this->cartService->total();
        $cartCount = $this->cartService->count();
    
        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Jumlah produk berhasil diperbarui.',
                'cart_item_id' => $item->id,
                'quantity' => $item->quantity,
                'subtotal' => ($item->variant?->price ?? $item->product->price)
                    * $item->quantity,
                'total' => $total,
                'cart_count' => $cartCount,
                'item_count' => $cartCount,
            ]);
        }
    
        return back()->with('success', 'Keranjang berhasil diperbarui.');
    }

    /**
     * Remove a product from the shopping cart.
     */
    public function destroy(Request $request, int $cartItem)
    {
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

}

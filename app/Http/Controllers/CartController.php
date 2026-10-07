<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\CartService;
use Illuminate\Http\Request;

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
        ]);

        $product = Product::findOrFail(
            $validated['product_id']
        );

        try {
            $this->cartService->add(
                $product,
                $validated['quantity']
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
    public function update(
        Request $request,
        Product $product
    ) {
        $validated = $request->validate([
            'quantity' => [
                'required',
                'integer',
                'min:1',
            ],
        ]);

        try {
            $this->cartService->update(
                $product->id,
                $validated['quantity']
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

        $total = $this->cartService->total();
        $cartCount = $this->cartService->count();

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Jumlah produk berhasil diperbarui.',
                'product_id' => $product->id,
                'quantity' => $validated['quantity'],
                'subtotal' => $product->price * $validated['quantity'],
                'total' => $total,
                'cart_count' => $cartCount,
                'item_count' => $cartCount,
            ]);
        }

        return back()->with(
            'success',
            'Keranjang berhasil diperbarui.'
        );
    }

    /**
     * Remove a product from the shopping cart.
     */
    public function destroy(
        Request $request,
        Product $product
    ) {
        $this->cartService->remove(
            $product->id
        );

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Produk berhasil dihapus dari keranjang.',
                'cart_count' => $this->cartService->count(),
            ]);
        }

        return back()->with(
            'success',
            'Produk berhasil dihapus dari keranjang.'
        );
    }
}

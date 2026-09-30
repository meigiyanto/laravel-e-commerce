<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Http\Request;

class CartController extends Controller
{
    /**
     * Display cart.
     */
    public function index()
    {
        $cart = Cart::firstOrCreate([
            'user_id' => auth()->id(),
        ]);

        $cart->load([
            'items.product.category',
            'items.product.subCategory',
        ]);

        $total = $cart->items->sum(function ($item) {
            return $item->product->price * $item->quantity;
        });

        return view('storefront.cart', compact(
            'cart',
            'total'
        ));
    }


    /**
     * Add product to cart.
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

        /*
        |---------------------------------------------------------        | Check Stock
        |---------------------------------------------------------        */

        if ($product->stock < 1) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Produk sedang habis.',
                ], 422);
            }

            return back()->with(
                'error',
                'Produk sedang habis.'
            );
        }

        /*
        |---------------------------------------------------------        | Get / Create Cart
        |---------------------------------------------------------        */
        $cart = Cart::firstOrCreate([
            'user_id' => auth()->id(),
        ]);

        /*
        |---------------------------------------------------------        | Existing Item
        |---------------------------------------------------------        */
        $cartItem = CartItem::where('cart_id', $cart->id)
            ->where('product_id', $product->id)
            ->first();

        $newQuantity = $validated['quantity'];

        if ($cartItem) {
            $newQuantity += $cartItem->quantity;
        }

        /*
        |---------------------------------------------------------        | Stock Validation
        |---------------------------------------------------------        */
        if ($newQuantity > $product->stock) {

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Jumlah produk melebihi stok yang tersedia.',
                ], 422);
            }

            return back()->with(
                'error',
                'Jumlah produk melebihi stok yang tersedia.'
            );
        }

        /*
        |---------------------------------------------------------        | Save
        |---------------------------------------------------------        */
        if ($cartItem) {

            $cartItem->update([
                'quantity' => $newQuantity,
            ]);
        } else {
            CartItem::create([
                'cart_id' => $cart->id,
                'product_id' => $product->id,
                'quantity' => $validated['quantity'],
            ]);

        }

        if ($request->expectsJson()) {
            $cartCount = $cart->items()->sum('quantity');

            return response()->json([
                'success' => true,
                'message' => 'Produk berhasil ditambahkan ke keranjang.',
                'cart_count' => $cartCount,
            ]);
        }

        return redirect()
            ->route('cart.index')
            ->with('success', 'Produk berhasil ditambahkan ke keranjang.');
    }


    /**
     * Update cart item.
     */
    public function update(Request $request, CartItem $cartItem)
    {
        $validated = $request->validate([
            'quantity' => [
                'required',
                'integer',
                'min:1',
            ],
        ]);

        /*
        |-------------------------------------------------------------
        | Security
        |-------------------------------------------------------------
        | Pastikan cart item milik user yang sedang login.
        */
        if ($cartItem->cart->user_id !== auth()->id()) {
            abort(403);
        }

        /*
        |-------------------------------------------------------------
        | Stock Validation
        |-------------------------------------------------------------
        */
        if ($validated['quantity'] > $cartItem->product->stock) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Jumlah melebihi stok yang tersedia.',
                ], 422);
            }

            return back()->with(
                'error',
                'Jumlah melebihi stok yang tersedia.'
            );
        }

        /*
        |-------------------------------------------------------------
        | Update Quantity
        |-------------------------------------------------------------
        */
        $cartItem->update([
            'quantity' => $validated['quantity'],
        ]);

        /*
        |-------------------------------------------------------------
        | AJAX Response
        |-------------------------------------------------------------
        */
        if ($request->expectsJson()) {
            $cart = $cartItem->cart->load('items.product');

            $subtotal = $cartItem->product->price * $cartItem->quantity;

            $total = $cart->items->sum(function ($item) {
                return $item->product->price * $item->quantity;
            });

            $cartCount = $cart->items->sum('quantity');

            return response()->json([
                'success' => true,
                'message' => 'Jumlah produk berhasil diperbarui.',
                'quantity' => $cartItem->quantity,
                'subtotal' => $subtotal,
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
     * Remove cart item.
     */
    public function destroy(CartItem $cartItem)
    {
        if ($cartItem->cart->user_id !== auth()->id()) {
            abort(403);
        }

        $cartItem->delete();

        return back()->with(
            'success',
            'Produk berhasil dihapus dari keranjang.'
        );
    }
}

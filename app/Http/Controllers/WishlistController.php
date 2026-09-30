<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class WishlistController extends Controller
{
    public function index(Request $request)
    {
        $products = $request->user()
            ->wishlistProducts()
            ->with('category')
            ->latest('wishlists.created_at')
            ->get();

        return view('storefront.wishlist', compact('products'));
    }

    public function store(Request $request, Product $product)
    {
        $request->user()
            ->wishlistProducts()
            ->syncWithoutDetaching([$product->id]);

        return back()->with('success', 'Produk ditambahkan ke wishlist.');
    }

    public function destroy(Request $request, Product $product)
    {
        $request->user()
            ->wishlistProducts()
            ->detach($product->id);

        return back()->with('success', 'Produk dihapus dari wishlist.');
    }
}

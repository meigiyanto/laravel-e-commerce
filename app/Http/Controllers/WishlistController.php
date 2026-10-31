<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WishlistController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $products = $request->user()
            ->wishlistProducts()
            ->with('category')
            ->latest('wishlists.created_at')
            ->get();

        return view('storefront.account.wishlist', compact('user', 'products'));
    }

    public function store(Request $request, Product $product)
    {
        $request->user()
            ->wishlistProducts()
            ->syncWithoutDetaching([$product->id]);
        
        if ($request->expectsJson()) {
    
            return response()->json([
                'success' => true,
                'message' => 'Produk ditambahkan ke wishlist.',
            ]);
        }

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

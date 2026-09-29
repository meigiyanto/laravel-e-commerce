<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;

class StorefrontController extends Controller
{
    public function home()
    {
        $categories = Category::withCount('products')
            ->orderBy('name')
            ->get();

        $latestProducts = Product::with([
            'category',
            'subCategory',
        ])
            ->latest()
            ->take(8)
            ->get();

        return view('storefront.home', compact(
            'categories',
            'latestProducts'
        ));
    }

    public function shop(Request $request)
    {
        $query = Product::with([
            'category',
            'subCategory',
        ]);

        if ($request->filled('q')) {
            $search = $request->input('q');

            $query->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->filled('category')) {
            $query->whereHas('category', function ($query) use ($request) {
                $query->where('slug', $request->input('category'));
            });
        }

        $products = $query
            ->latest()
            ->paginate(12)
            ->withQueryString();

        $categories = Category::orderBy('name')->get();

        return view('storefront.shop', compact(
            'products',
            'categories'
        ));
    }

    public function product(string $slug)
    {
        $product = Product::with([
            'category',
            'subCategory',
        ])
            ->where('slug', $slug)
            ->firstOrFail();

        $relatedProducts = Product::with([
            'category',
            'subCategory',
        ])
            ->where('id', '!=', $product->id)
            ->where('category_id', $product->category_id)
            ->latest()
            ->take(4)
            ->get();

        return view('storefront.product', compact(
            'product',
            'relatedProducts'
        ));
    }

    public function category(string $slug)
    {
        $category = Category::where('slug', $slug)->firstOrFail();

        $products = Product::with([
            'category',
            'subCategory',
        ])
            ->where('category_id', $category->id)
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('storefront.category', compact(
            'category',
            'products'
        ));
    }
}

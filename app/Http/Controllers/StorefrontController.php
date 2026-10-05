<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Services\Storefront\ProductFilter;
use Illuminate\Http\Request;

class StorefrontController extends Controller
{
    public function __construct(
        private readonly ProductFilter $productFilter
    ) {
    }

    public function home()
    {
        $categories = Category::withCount('products')
            ->orderBy('name')
            ->get();

        $latestProducts = Product::with([
            'category',
            'subCategory',
        ])
            ->withAvg('reviews', 'rating')
            ->withCount('reviews')
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
        ])
            ->withAvg('reviews', 'rating')
            ->withCount('reviews');

        $this->productFilter->apply(
            $query,
            $request
        );

        $products = $query
            ->paginate(12)
            ->withQueryString();

        $categories = Category::with([
            'subCategories' => function ($query) {
                $query->orderBy('name');
            },
        ])
            ->orderBy('name')
            ->get();

        return view(
            'storefront.shop',
            compact(
                'products',
                'categories'
            )
        );
    }

    public function product(string $slug)
    {
        $product = Product::with([
            'category',
            'subCategory',
            'specifications',
            'reviews.user',
        ])
            ->withAvg('reviews', 'rating')
            ->withCount('reviews')
            ->where('slug', $slug)
            ->firstOrFail();

        $relatedProducts = Product::with([
            'category',
            'subCategory',
        ])
            ->withAvg('reviews', 'rating')
            ->withCount('reviews')
            ->where('id', '!=', $product->id)
            ->where('category_id', $product->category_id)
            ->latest()
            ->take(4)
            ->get();

        return view(
            'storefront.product',
            compact(
                'product',
                'relatedProducts'
            )
        );
    }

    public function category(
        Request $request,
        string $slug
    ) {
        $category = Category::where(
            'slug',
            $slug
        )->firstOrFail();

        $query = Product::with([
            'category',
            'subCategory',
        ])
            ->withAvg('reviews', 'rating')
            ->withCount('reviews')
            ->where(
                'category_id',
                $category->id
            );

        $this->productFilter->apply(
            $query,
            $request
        );

        $products = $query
            ->paginate(12)
            ->withQueryString();

        return view(
            'storefront.category',
            compact(
                'category',
                'products'
            )
        );
    }
}

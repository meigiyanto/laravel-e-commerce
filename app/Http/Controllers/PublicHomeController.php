<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Contracts\View\View;

class PublicHomeController extends Controller
{
    public function __invoke(): View
    {
        $categories = Category::withCount('products')
            ->orderBy('name')
            ->take(8)
            ->get();

        $latestProducts = Product::with([ 'category', 'subCategory', 'specifications', 'variants.inventory', 'variants.attributeValues.attribute'])->withAvg('reviews', 'rating')
            ->withCount('reviews')
            ->latest()
            ->take(8)
            ->get();

        return view('storefront.home', compact('categories','latestProducts'));
    }
}
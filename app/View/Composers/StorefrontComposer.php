<?php

namespace App\View\Composers;

use App\Models\Category;
use Illuminate\View\View;

class StorefrontComposer
{
    public function compose(View $view): void
    {
        $storefrontCategories = Category::query()
            ->orderBy('name')
            ->get([
                'id',
                'name',
                'slug',
            ]);
    
        $storefrontTopCategories = Category::query()
            ->withCount('products')
            ->orderByDesc('products_count')
            ->orderBy('name')
            ->limit(6)
            ->get([
                'id',
                'name',
                'slug',
            ]);
    
        $view->with([
            'storefrontCategories' => $storefrontCategories,
            'storefrontTopCategories' => $storefrontTopCategories,
        ]);
    }
} 
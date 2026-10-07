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

        $view->with(
            'storefrontCategories',
            $storefrontCategories
        );
    }
} 
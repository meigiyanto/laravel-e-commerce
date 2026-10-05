<?php

namespace App\Services\Storefront;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class ProductFilter
{
    public function apply(
        Builder $query,
        Request $request
    ): Builder {
        $this->search(
            $query,
            $request->input('q')
        );

        $this->category(
            $query,
            $request->input('category')
        );

        $this->subCategory(
            $query,
            $request->input('subcategory'),
            $request->input('category')
        );

        $this->sort(
            $query,
            $request->input('sort')
        );

        return $query;
    }

    private function search(
        Builder $query,
        mixed $value
    ): void {
        $search = trim((string) $value);

        if ($search === '') {
            return;
        }

        $query->where(function (Builder $query) use ($search) {
            $query
                ->where('name', 'like', "%{$search}%")
                ->orWhere(
                    'description',
                    'like',
                    "%{$search}%"
                );
        });
    }

    private function category(
        Builder $query,
        mixed $value
    ): void {
        $slug = trim((string) $value);

        if ($slug === '') {
            return;
        }

        $query->whereRelation(
            'category',
            'slug',
            $slug
        );
    }

    private function subCategory(
        Builder $query,
        mixed $subCategoryValue,
        mixed $categoryValue
    ): void {
        $subCategorySlug = trim((string) $subCategoryValue);
        $categorySlug = trim((string) $categoryValue);

        if ($subCategorySlug === '') {
            return;
        }

        /*
         * Jika kategori dipilih, subkategori wajib
         * berasal dari kategori tersebut.
         */
        $query->whereHas(
            'subCategory',
            function (Builder $subCategoryQuery) use (
                $subCategorySlug,
                $categorySlug
            ) {
                $subCategoryQuery->where(
                    'slug',
                    $subCategorySlug
                );

                if ($categorySlug !== '') {
                    $subCategoryQuery->whereRelation(
                        'category',
                        'slug',
                        $categorySlug
                    );
                }
            }
        );
    }

    private function sort(
        Builder $query,
        mixed $value
    ): void {
        match ((string) $value) {
            'oldest' => $query
                ->oldest(),

            'price_low' => $query
                ->orderBy('price')
                ->orderByDesc('id'),

            'price_high' => $query
                ->orderByDesc('price')
                ->orderByDesc('id'),

            'name_asc' => $query
                ->orderBy('name')
                ->orderByDesc('id'),

            'name_desc' => $query
                ->orderByDesc('name')
                ->orderByDesc('id'),

            default => $query
                ->latest(),
        };
    }
}

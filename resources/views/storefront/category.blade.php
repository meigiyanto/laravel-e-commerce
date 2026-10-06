@extends('layouts.storefront')

@section('title', $category->name . ' - MeiStore')

@section('content')

    {{-- BREADCRUMB --}}
    <div class="store-breadcrumb">

        <div class="container">

            <nav aria-label="breadcrumb">

                <ol class="breadcrumb mb-0">

                    <li class="breadcrumb-item">

                        <a href="{{ route('storefront.home') }}">

                            <i class="bi bi-house me-1"></i>

                            Home

                        </a>

                    </li>

                    <li class="breadcrumb-item">

                        <a href="{{ route('storefront.shop') }}">
                            Shop
                        </a>

                    </li>

                    <li
                        class="breadcrumb-item active"
                        aria-current="page"
                    >
                        {{ $category->name }}
                    </li>

                </ol>

            </nav>

        </div>

    </div>


    {{-- CATEGORY HEADER --}}
    <section class="store-section pb-3">

        <div class="container">

            <div class="row align-items-end g-3">

                <div class="col-lg-8">

                    <div class="store-category-heading">

                        <i class="bi bi-grid"></i>

                        Kategori Produk

                    </div>

                    <h1
                        class="store-section-title mb-2"
                    >
                        {{ $category->name }}
                    </h1>

                    <p class="store-section-subtitle mb-0">

                        Temukan berbagai produk dalam kategori
                        {{ $category->name }}.

                    </p>

                </div>

                <div class="col-lg-4 text-lg-end">

                    <span class="text-muted small">

                        <strong class="text-dark">
                            {{ $products->total() }}
                        </strong>

                        produk tersedia

                    </span>

                </div>

            </div>


            {{-- SUBCATEGORY NAVIGATION --}}
            @if ($category->subCategories->isNotEmpty())

                <div class="store-subcategory-nav">

                    <span class="store-subcategory-label">
                        Subkategori:
                    </span>

                    @foreach ($category->subCategories as $subCategory)

                        <a
                            href="{{ route('storefront.category', $category->slug) }}?subcategory={{ $subCategory->slug }}"
                            class="store-subcategory-link
                                {{ request('subcategory') === $subCategory->slug
                                    ? 'active'
                                    : ''
                                }}"
                        >
                            {{ $subCategory->name }}
                        </a>

                    @endforeach

                </div>

            @endif

        </div>

    </section>


    {{-- REUSABLE FILTER TOOLBAR --}}
    <section class="pb-4">

        <div class="container">

            <x-store-product-toolbar
                :category-context="$category"
                :action="route('storefront.category', $category->slug)"
            />

        </div>

    </section>


    {{-- PRODUCTS --}}
    <section class="store-section pt-0">

        <div class="container">

            @if ($products->count())

                <div class="row g-3 g-lg-4">

                    @foreach ($products as $product)

                        <div class="col-6 col-md-4 col-lg-3">

                            <x-store-product-card
                                :product="$product"
                            />

                        </div>

                    @endforeach

                </div>


                @if ($products->hasPages())

                    <div class="d-flex justify-content-center mt-5">

                        {{ $products
                            ->onEachSide(1)
                            ->links('pagination::bootstrap-5')
                        }}

                    </div>

                @endif

            @else

                <div class="store-empty-state">

                    <div class="store-empty-icon">

                        <i class="bi bi-box-seam"></i>

                    </div>

                    <h2 class="store-empty-title">
                        Belum Ada Produk
                    </h2>

                    <p class="store-empty-text">

                        Belum ada produk yang sesuai
                        dengan filter dalam kategori ini.

                    </p>

                    <a
                        href="{{ route('storefront.category', $category->slug) }}"
                        class="store-btn-primary"
                    >
                        <i class="bi bi-arrow-counterclockwise"></i>
                        Reset Filter
                    </a>

                </div>

            @endif

        </div>

    </section>

@endsection

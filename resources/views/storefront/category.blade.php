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
                        <a href="{{ route('storefront.shop') }}">Shop</a>
                    </li>
                    <li class="breadcrumb-item active" aria-current="page">
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
                        Product Category
                    </div>

                    <h1 class="store-section-title mb-2">{{ $category->name }}</h1>
                    <p class="store-section-subtitle mb-0">Find various products in category {{ $category->name }}.</p>
                </div>

                <div class="col-lg-4 text-lg-end">
                    <span class="text-muted small">
                        <strong class="text-dark">{{ $products->total() }}</strong> Product available</span>
                </div>
            </div>
        </div>
    </section>


    {{-- REUSABLE FILTER TOOLBAR --}}
    <section class="pb-4">
        <div class="container">
            <x-store-product-toolbar :category-context="$category" :action="route('storefront.category', $category->slug)"/>
        </div>
    </section>


    {{-- PRODUCTS --}}
    <section class="store-section pt-0">
        <div class="container">
            @if ($products->count())
                <div class="row g-3 g-lg-4">
                    @foreach ($products as $product)
                        <div class="col-6 col-md-4 col-lg-3">
                            <x-store-product-card :product="$product" />
                        </div>
                    @endforeach
                </div>
                @if ($products->hasPages())
                    <div class="mt-3">
                        {{ $products->onEachSide(1)->links('pagination::bootstrap-5') }}
                    </div>
                @endif
            @else
                <div class="store-empty-state">
                    <div class="store-empty-icon">
                        <i class="bi bi-box-seam"></i>
                    </div>
                    <h2 class="store-empty-title">Product unavailable</h2>
                    <p class="store-empty-text">There are no products matching the filters in this category yet.</p>

                    <a href="{{ route('storefront.category', $category->slug) }}"  class="store-btn-primary">
                        <i class="bi bi-arrow-counterclockwise"></i>
                        Reset Filter
                    </a>
                </div>
            @endif
        </div>
    </section>
@endsection

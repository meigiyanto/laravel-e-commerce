@extends('layouts.storefront')

@section('title', 'Shop - MeiStore')

@section('content')

{{-- =========================================================
     BREADCRUMB
========================================================= --}}
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
                <li class="breadcrumb-item active" aria-current="page">
                    Shop
                </li>
            </ol>
        </nav>
    </div>
</div>

{{-- =========================================================
     SHOP HEADER
========================================================= --}}
<section class="store-section pb-3">
    <div class="container">
        <div class="store-section-header">
            <div>
                <h1 class="store-section-title">All Products<h1>
                <p class="store-section-subtitle">Find the best products here</p>
            </div>

            <div class="text-muted small">
                <i class="bi bi-box-seam me-1"></i>
                {{ $products->total() }} product
            </div>
        </div>
    </div>
</section>

{{-- =========================================================
     FILTER
========================================================= --}}
<section class="pb-4">
    <div class="container">

        <x-store-product-toolbar
            :categories="$categories"
            :action="route('storefront.shop')"
        />

    </div>
</section>

{{-- =========================================================
     RESULT HEADER
========================================================= --}}
<section class="pb-3">
    <div class="container">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                @if ($products->total() > 0)
                    <span class="small text-muted">
                        Showing
                        <strong class="text-dark">{{ $products->firstItem() }}</strong>
                        -
                        <strong class="text-dark">{{ $products->lastItem() }}</strong>
                        of
                        <strong class="text-dark">{{ $products->total() }}</strong>
                        product
                    </span>
                @else
                    <span class="small text-muted">
                        Product not found
                    </span>
                @endif
            </div>
        </div>
    </div>
</section>


{{-- =========================================================
     PRODUCTS
========================================================= --}}
<section class="pb-5">
    <div class="container">
        @if ($products->count())
            <div class="row g-3 g-md-4">
                @foreach ($products as $product)
                    <div class="col-6 col-md-4 col-lg-3">
                        <x-store-product-card :product="$product"/>
                    </div>
                @endforeach
            </div>
            {{-- =================================================
                 PAGINATION
            ================================================== --}}
            @if ($products->hasPages())
                <div class="mt-3">
                    {{ $products->onEachSide(1)->links('pagination::bootstrap-5') }}
                </div>
            @endif
        @else
            {{-- Empty state --}}
            <div class="shop-empty-state">
                <div class="shop-empty-icon"><i class="bi bi-search"></i></div>

                <h2 class="h5 fw-bold mb-2">Product not found<h2>
                <p class="text-muted mb-4">No products match the selected filters.</p>
                <a
                    href="{{ route('storefront.shop') }}"
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

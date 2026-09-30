@extends('layouts.storefront')

@section('title', 'Shop - MeiStore')

@section('content')
<section class="container py-4 py-lg-5">
    {{-- Header --}}
    <div class="mb-4">
        <h1 class="section-title mb-2">
            Shop
        </h1>
        <p class="text-muted mb-0">
            Search product that you need.
        </p>
    </div>

    {{-- Search + Filter --}}
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-3 p-md-4">
            <form
                action="{{ route('storefront.shop') }}"
                method="GET"
            >
                <div class="row g-3 align-items-end">
                    {{-- Search --}}
                    <div class="col-lg-6">
                        <label
                            for="q"
                            class="form-label fw-semibold"
                        >
                            Search Product
                        </label>

                        <div class="input-group">
                            <span class="input-group-text bg-white">
                                <i class="bi bi-search"></i>
                            </span>
                            <input
                                type="search"
                                id="q"
                                name="q"
                                class="form-control"
                                placeholder="Search product name..."
                                value="{{ request('q') }}"
                            >
                        </div>
                    </div>


                    {{-- Category --}}
                    <div class="col-lg-4">

                        <label
                            for="category"
                            class="form-label fw-semibold"
                        >
                            Category
                        </label>

                        <select
                            id="category"
                            name="category"
                            class="form-select"
                        >

                            <option value="">All Category</option>
                            @foreach ($categories as $category)
                                <option
                                    value="{{ $category->slug }}"
                                    @selected(request('category') === $category->slug)
                                >
                                    {{ $category->name }}
                                </option>

                            @endforeach
                        </select>
                    </div>


                    {{-- Submit --}}
                    <div class="col-lg-2">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-search me-1"></i>
                            Search
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>


    {{-- Active Filters --}}
    @if (request('q') || request('category'))
        <div class="d-flex flex-wrap align-items-center gap-2 mb-4">
            <span class="text-muted">Filter aktive: </span>
            @if (request('q'))
                <span class="badge bg-primary-subtle text-primary">
                    <i class="bi bi-search me-1"></i>
                    {{ request('q') }}
                </span>
            @endif

            @if (request('category'))
                @php
                    $selectedCategory = $categories->firstWhere('slug', request('category'));
                @endphp
                @if ($selectedCategory)
                    <span class="badge bg-primary-subtle text-primary">
                        <i class="bi bi-grid me-1"></i>
                        {{ $selectedCategory->name }}
                    </span>
                @endif
            @endif

            <a
                href="{{ route('storefront.shop') }}"
                class="btn btn-sm btn-outline-secondary"
            >
                Reset
            </a>
        </div>
    @endif


    {{-- Result Info --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="h5 fw-bold mb-1">Product</h2>
            <p class="text-muted small mb-0">Showing {{ $products->firstItem() ?? 0 }} - {{ $products->lastItem() ?? 0 }} of {{ $products->total() }} products</p>
        </div>
    </div>


    {{-- Products --}}
    <div class="row g-4">
        @forelse ($products as $product)
            <div class="col-6 col-md-4 col-lg-3">
                <div class="product-card">
                    <a href="{{ route('storefront.product', $product->slug) }}">
                        @if ($product->image)
                            <img
                                src="{{ $product->image }}"
                                alt="{{ $product->name }}"
                                class="product-image"
                                loading="lazy"
                            >
                        @else
                            <div class="product-image d-flex align-items-center justify-content-center">
                                <i class="bi bi-image fs-1 text-secondary"></i>
                            </div>
                        @endif
                    </a>

                    {{-- Body --}}
                    <div class="product-body">
                        <div class="product-category">
                            {{ $product->category?->name ?? 'Tanpa kategori' }}
                        </div>

                        @if ($product->subCategory)
                            <div class="text-muted small">
                                {{ $product->subCategory->name }}
                            </div>
                        @endif

                        <a href="{{ route('storefront.product', $product->slug) }}">
                            <h3 class="product-name">{{ $product->name }}</h3>
                        </a>

                        <div class="product-price">
                            Rp {{ number_format($product->price, 0, ',', '.') }}
                        </div>

                        <div class="product-stock mt-1">
                            @if ($product->stock > 0)
                                <i class="bi bi-check-circle text-success"></i>
                                Stock {{ $product->stock }}
                            @else
                                <i class="bi bi-x-circle text-danger"></i>
                                Out of stock
                            @endif
                        </div>

                        {{-- Wish List --}}
                        <form action="{{ route('wishlist.store', $product) }}" method="POST" class="mt-2">
                            @csrf
                            <button class="btn btn-outline-danger w-100">
                                <i class="bi bi-heart me-1"></i>
                                Wishlist
                            </button>
                        </form>

                        {{-- Compare --}}
                        <form action="{{ route('compare.store', $product) }}" method="POST" class="mt-2">
                            @csrf
                            <button class="btn btn-outline-secondary w-100">
                                <i class="bi bi-bar-chart me-1"></i>
                                Compare
                            </button>
                        </form>

                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="text-center py-5">
                    <i class="bi bi-search fs-1 text-muted"></i>
                    <h3 class="h5 fw-bold mt-3">Product not found</h3>
                    <p class="text-muted">Try to using keyword or category</p>
                    <a
                        href="{{ route('storefront.shop') }}"
                        class="btn btn-primary"
                    >
                        See all product
                    </a>
                </div>
            </div>
        @endforelse
    </div>


    {{-- Pagination --}}
    @if ($products->hasPages())
        <div class="d-flex justify-content-center mt-5">
            {{ $products->onEachSide(1)->links('pagination::bootstrap-5') }}
        </div>
    @endif

</section>
@endsection

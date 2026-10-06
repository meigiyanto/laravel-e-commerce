@extends('layouts.storefront')

@section('title', 'Wishlist - MeiStore')

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
                    Wish List
                </li>
            </ol>
        </nav>
    </div>
</div>

<section class="store-section pb-3">
    <div class="container">
        <div class="store-section-header">
            <div>
                <h1 class="store-section-title">Wish List<h1>
                <p class="store-section-subtitle">Products you saved to buy later are available here.</p>
            </div>
        </div>

        @if ($products->isEmpty())
            <div class="alert alert-light border">
                <h3>Wishlist Empty</h3>
                <p><i class="bi bi-heart me-2"></i>
                Oops! Wishlist is empty.</p>
                <a href="{{ route('storefront.shop') }}" class="btn btn-primary">
                    <i class="bi bi-cart"></i> Start Shopping
                </a>
            </div>
        @else
            <div class="row g-4">
                @foreach ($products as $product)
                    <div class="col-6 col-md-4 col-lg-3">
                        <div class="card">
                            <a href="{{ route('storefront.product', $product->slug) }}">
                                @if ($product->image)
                                    <img class="card-img-top" src="{{ $product->image }}" alt="{{ $product->name }}" height="200">
                                @else
                                    <div class="product-image d-flex align-items-center justify-content-center">
                                        <i class="bi bi-image fs-1 text-secondary"></i>
                                    </div>
                                @endif
                            </a>

                            <div class="card-body">
                                <div class="product-category">
                                    {{ $product->category?->name ?? 'Tanpa kategori' }}
                                </div>

                                <a href="{{ route('storefront.product', $product->slug) }}">
                                    <h5 class="card-title">{{ $product->name }}</h5>
                                </a>

                                <div class="card-text">
                                    Rp {{ number_format($product->price, 0, ',', '.') }}
                                </div>

                                <form action="{{ route('wishlist.destroy', $product) }}" method="POST" class="mt-3">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-outline-danger w-100">
                                        <i class="bi bi-heart-fill me-1"></i>
                                        Remove
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</section>
@endsection

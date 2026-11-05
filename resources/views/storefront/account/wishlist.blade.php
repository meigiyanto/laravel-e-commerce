@extends('layouts.storefront')

@section('title', config('app.name') . ' - Wishlist')

@section('content')
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
                <li class="breadcrumb-item">Account</li>
                <li class="breadcrumb-item active" aria-current="page">
                    My Wishlist
                </li>
            </ol>
        </nav>
    </div>
</div>

@include('components.account.page-header', [
    'title' => 'My Wishlist',
    'description' => 'Keep the products you love in one place and come back to them anytime.',
    'eyebrow' => 'Your personal collection',
    'action' => '<a href="' . route('storefront.shop') . '" class="store-btn-primary"><i class="bi bi-bag me-2"></i>Continue Shopping</a>',
])

<section class="pb-5">
    <div class="container">
        <div class="row g-4">
            {{-- Sidebar --}}
            <div class="col-lg-4">
                @include('components.account.sidebar')
            </div>

            {{-- Main Content --}}
            <div class="col-lg-8">
                @if ($products->isEmpty())
                    <div class="store-empty-state">
                        <div class="store-empty-icon">
                            <i class="bi bi-heart"></i>
                        </div>
        
                        <h2 class="store-empty-title">Your wishlist is empty</h2>
                        <p class="store-empty-text">
                            Found something you like? Save it to your wishlist and it will be waiting here for you.
                        </p>
        
                        <a href="{{ route('storefront.shop') }}" class="store-btn-primary">
                            <i class="bi bi-shop me-1"></i>
                            Explore products
                        </a>
                    </div>
                @else
                    <div class="store-wishlist-summary">
                        <span>
                            <i class="bi bi-heart-fill me-1"></i>
                            {{ $products->count() }} {{ \Illuminate\Support\Str::plural('saved product', $products->count()) }}
                        </span>
                    </div>
        
                    <div class="row g-3 g-md-4">
                        @foreach ($products as $product)
                            <div class="col-6 col-md-4 col-lg-3">
                                <article class="store-product-card store-wishlist-card">
                                    <a
                                        href="{{ route('storefront.product', $product->slug) }}"
                                        class="store-product-image-link"
                                        aria-label="View {{ $product->name }}"
                                    >
                                        <div class="store-product-image-wrap">
                                            @if ($product->image_url)
                                                <img
                                                    src="{{ $product->image_url }}"
                                                    alt="{{ $product->name }}"
                                                    class="store-product-image"
                                                    loading="lazy"
                                                >
                                            @else
                                                <div class="store-product-placeholder">
                                                    <i class="bi bi-image"></i>
                                                </div>
                                            @endif
        
                                            <span class="store-wishlist-saved-icon" aria-label="Saved to wishlist">
                                                <i class="bi bi-heart-fill"></i>
                                            </span>
                                        </div>
                                    </a>
        
                                    <div class="store-product-body">
                                        <div class="store-product-category">
                                            {{ $product->category?->name ?? 'Uncategorized' }}
                                        </div>
        
                                        <h2 class="store-product-name">
                                            <a href="{{ route('storefront.product', $product->slug) }}">
                                                {{ $product->name }}
                                            </a>
                                        </h2>
        
                                        <div class="store-product-price">
                                            Rp {{ number_format($product->price, 0, ',', '.') }}
                                        </div>
        
                                        <div class="store-wishlist-actions">
                                            <a
                                                href="{{ route('storefront.product', $product->slug) }}"
                                                class="store-wishlist-view"
                                            >
                                                <i class="bi bi-eye me-1"></i>
                                                View product
                                            </a>
        
                                            <form
                                                action="{{ route('account.wishlist.destroy', $product) }}"
                                                method="POST"
                                                class="store-wishlist-remove-form"
                                            >
                                                @csrf
                                                @method('DELETE')
        
                                                <button
                                                    type="submit"
                                                    class="store-wishlist-remove"
                                                    aria-label="Remove {{ $product->name }} from wishlist"
                                                    title="Remove from wishlist"
                                                >
                                                    <i class="bi bi-trash3"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </article>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</section>
@endsection
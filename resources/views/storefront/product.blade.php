@extends('layouts.storefront')

@section('title', $product->name . ' - MeiStore')

@section('content')

<section class="container py-4 py-lg-5">
    {{-- Breadcrumb --}}
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item">
                <a href="{{ route('storefront.home') }}">Home</a>
            </li>

            <li class="breadcrumb-item">
                <a href="{{ route('storefront.shop') }}">Shop</a>
            </li>

            @if ($product->category)
                <li class="breadcrumb-item">
                    <a href="{{ route('storefront.category', $product->category->slug) }}">
                        {{ $product->category->name }}
                    </a>
                </li>
            @endif
            <li class="breadcrumb-item active" aria-current="page">
                {{ $product->name }}
            </li>
        </ol>
    </nav>


    {{-- Product --}}
    <div class="row g-4 g-lg-5">
        {{-- Image --}}
        <div class="col-lg-6">
            <div class="product-detail-image-wrapper">
                @if ($product->image)
                    <img src="{{ $product->image }}" alt="{{ $product->name }}" class="product-detail-image">
                @else
                    <div class="product-detail-image-placeholder">
                        <i class="bi bi-image"></i>
                    </div>
                @endif
            </div>
        </div>


        {{-- Information --}}
        <div class="col-lg-6">
            <div class="product-category mb-2">
                {{ $product->category?->name ?? 'Tanpa kategori' }}
                @if ($product->subCategory)
                    <span class="mx-1">•</span>
                    {{ $product->subCategory->name }}
                @endif
            </div>


            <h1 class="display-6 fw-bold mb-3">
                {{ $product->name }}
            </h1>

            <div class="mb-3">
                @if ($product->reviews_count)
                    <span class="text-warning">
                        @for ($i = 1; $i <= 5; $i++)
                            <i class="bi bi-star{{ $i <= round($product->reviews_avg_rating) ? '-fill' : '' }}"></i>
                        @endfor
                    </span>
                    <strong>{{ number_format($product->reviews_avg_rating, 1) }}</strong>
                    <span class="text-muted">({{ $product->reviews_count }} review)</span>
                @else
                    <span class="text-muted">Review unavailable</span>
                @endif
            </div>

            <div class="product-detail-price mb-3">
                Rp {{ number_format($product->price, 0, ',', '.') }}
            </div>


            {{-- Stock --}}
            @if ($product->stock > 0)
                <div class="alert alert-success d-inline-flex align-items-center py-2 px-3">
                    <i class="bi bi-check-circle me-2"></i>
                    Stok available: <strong class="ms-1">{{ $product->stock }}</strong>
                </div>
            @else
                <div class="alert alert-danger d-inline-flex align-items-center py-2 px-3">
                    <i class="bi bi-x-circle me-2"></i>
                    Product is out of stock.
                </div>
            @endif


            {{-- Description --}}
            <div class="mt-4">
                <h2 class="h5 fw-bold">Product Description</h2>
                <div class="text-muted product-description">
                    @if ($product->description)
                        {!! nl2br(e($product->description)) !!}
                    @else
                        There is no product description yet.
                    @endif
                </div>
            </div>


            {{-- Add To Cart --}}
            @if ($product->stock > 0)
                @auth
                    <form action="{{ route('cart.store') }}" method="POST" class="mt-4">
                        @csrf
                        <input
                            type="hidden"
                            name="product_id"
                            value="{{ $product->id }}"
                        >

                        <div class="row g-2">
                            <div class="col-4 col-sm-3">
                                <label for="quantity" class="form-label fw-semibold">Total</label>
                                <input
                                    type="number"
                                    id="quantity"
                                    name="quantity"
                                    class="form-control"
                                    value="1"
                                    min="1"
                                    max="{{ $product->stock }}"
                                    required
                                >

                            </div>
                            <div class="col">
                                <label class="form-label d-block">&nbsp; </label>
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="bi bi-cart-plus me-2"></i>
                                    Add to cart
                                </button>
                            </div>
                        </div>
                    </form>
                @else
                    <div class="alert alert-light border mt-4">
                        <i class="bi bi-info-circle me-2"></i>
                        Please login first to add products to your cart.
                        <div class="mt-3">
                            <a href="{{ route('login') }}" class="btn btn-primary">Login</a>
                            <a href="{{ route('register') }}" class="btn btn-outline-primary ms-2">Sign Upr</a>
                        </div>
                    </div>
                @endauth
            @else
                <button type="button" class="btn btn-secondary w-100 mt-4" disabled>
                    Out of Stock.
                </button>
            @endif
        </div>
    </div>

    {{-- Product Compare --}}
    <form action="{{ route('compare.store', $product) }}" method="POST" class="mt-2">
        @csrf
        <button class="btn btn-outline-secondary w-100">
            <i class="bi bi-bar-chart me-1"></i>
            Compare Product
        </button>
    </form>

    {{-- Product Wishlist --}}
    <form action="{{ route('wishlist.store', $product) }}" method="POST" class="mt-2">
        @csrf
        <button class="btn btn-outline-danger w-100">
            <i class="bi bi-heart me-1"></i>
            Add to Wishlist
        </button>
    </form>

    {{-- Review Product --}}
    <div id="review" class="mt-5 pt-5 border-top">
        <h2 class="h4 fw-bold mb-4">Product Review</h2>
        @auth
            @php
                $myReview = $product->reviews->firstWhere('user_id', auth()->id());
                $canReview = auth()->user()->orders()
                    ->where('status', 'completed')
                    ->whereHas('items', fn ($q) => $q->where('product_id', $product->id))
                    ->exists();
            @endphp

            @if ($myReview)
                <div class="alert alert-light border">
                    You have already given a review for this product
                    Kamu sudah memberikan review untuk produk ini.
                </div>

                <form action="{{ route('reviews.update', $myReview) }}" method="POST" class="border rounded p-3 mb-4">
                    @csrf
                    @method('PATCH')

                    <label class="form-label fw-semibold">Rating</label>
                    <select name="rating" class="form-select mb-3" required>
                        @for ($i = 5; $i >= 1; $i--)
                            <option value="{{ $i }}" @selected($myReview->rating == $i)>
                                {{ $i }} Star
                            </option>
                        @endfor
                    </select>
                    <input name="title" class="form-control mb-3" value="{{ $myReview->title }}" placeholder="Judul review">
                    <textarea name="comment" class="form-control mb-3" rows="3" required>{{ $myReview->comment }}</textarea>
                    <button class="btn btn-primary">Update Review</button>
                </form>

                <form action="{{ route('reviews.destroy', $myReview) }}" method="POST">
                    @csrf
                    @method('DELETE')
                    <button class="btn btn-outline-danger btn-sm" onclick="return confirm('Delete this review?')">Delete Review</button>
                </form>

            @elseif ($canReview)
                <form action="{{ route('reviews.store', $product) }}" method="POST"
                    class="border rounded p-3 mb-4">
                    @csrf

                    <label class="form-label fw-semibold">Rating</label>

                    <select name="rating" class="form-select mb-3" required>
                        <option value="">Pilih rating</option>
                        @for ($i = 5; $i >= 1; $i--)
                            <option value="{{ $i }}">{{ $i }} Bintang</option>
                        @endfor
                    </select>

                    <input name="title" class="form-control mb-3" placeholder="Review title">

                    <textarea name="comment" class="form-control mb-3" rows="3" placeholder="How are your experience?" required></textarea>

                    <button class="btn btn-primary">Submit Review</button>
                </form>
            @else
                <div class="alert alert-light border">
                    Review only available after you buy this product and order status <strong>Completed</strong>.
                </div>
            @endif
        @else
            <div class="alert alert-light border">
                Please login to leave a review.
            </div>
        @endauth

        @forelse ($product->reviews as $review)
            <div class="border-bottom py-3">
                <div class="d-flex justify-content-between">
                    <strong>{{ $review->user->name }}</strong>

                    @if ($review->is_verified)
                        <span class="badge bg-success">
                            Verified Purchase
                        </span>
                    @endif
                </div>

                <div class="text-warning my-1">
                    @for ($i = 1; $i <= 5; $i++)
                        <i class="bi bi-star{{ $i <= $review->rating ? '-fill' : '' }}"></i>
                    @endfor
                </div>

                @if ($review->title)
                    <strong>{{ $review->title }}</strong>
                @endif

                <p class="mb-1">{{ $review->comment }}</p>

                <small class="text-muted">
                    {{ $review->created_at->format('d M Y') }}
                </small>
            </div>
        @empty
            <p class="text-muted">Rewiew unavailable for this product.</p>
        @endforelse
    </div>

    {{-- Related Products --}}
    @if ($relatedProducts->isNotEmpty())
        <div class="mt-5 pt-5 border-top">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="section-title mb-1">Related Product</h2>
                    <p class="text-muted mb-0">Other products from the same cart.</p>
                </div>

                @if ($product->category)
                    <a href="{{ route('storefront.category', $product->category->slug) }}" class="section-link">See all<i class="bi bi-arrow-right"></i>
                    </a>
                @endif
            </div>

            <div class="row g-4">
                @foreach ($relatedProducts as $relatedProduct)
                    <div class="col-6 col-md-4 col-lg-3">
                        <div class="product-card">
                            <a href="{{ route('storefront.product', $relatedProduct->slug) }}">
                                @if ($relatedProduct->image)
                                    <img
                                        src="{{ $relatedProduct->image }}"
                                        alt="{{ $relatedProduct->name }}"
                                        class="product-image"
                                        loading="lazy"
                                    >
                                @else
                                    <div class="product-image d-flex align-items-center justify-content-center">
                                        <i class="bi bi-image fs-1 text-secondary"></i>
                                    </div>
                                @endif
                            </a>
                            <div class="product-body">
                                <div class="product-category">
                                    {{ $relatedProduct->category?->name }}
                                </div>

                                <a href="{{ route('storefront.product', $relatedProduct->slug) }}">
                                    <h3 class="product-name">{{ $relatedProduct->name }}</h3>
                                </a>
                                <div class="product-price">
                                    Rp {{ number_format($relatedProduct->price, 0, ',', '.') }}
                                </div>

                                <a href="{{ route('storefront.product', $relatedProduct->slug) }}" class="btn btn-outline-primary w-100 mt-3">See Product</a>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</section>
@endsection

@push('styles')
<style>
    .product-detail-image-wrapper {
        background: #f8f9fa;
        border: 1px solid #e5e7eb;
        border-radius: 1.25rem;
        overflow: hidden;
        min-height: 450px;
    }

    .product-detail-image {
        width: 100%;
        height: 520px;
        object-fit: cover;
        display: block;
    }

    .product-detail-image-placeholder {
        height: 520px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #adb5bd;
        font-size: 5rem;
    }

    .product-detail-price {
        color: #0d6efd;
        font-size: 2rem;
        font-weight: 800;
    }

    .product-description {
        line-height: 1.8;
    }

    @media (max-width: 767.98px) {
        .product-detail-image-wrapper {
            min-height: 300px;
        }

        .product-detail-image,
        .product-detail-image-placeholder {
            height: 320px;
        }

        .product-detail-price {
            font-size: 1.6rem;
        }
    }
</style>
@endpush

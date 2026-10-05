@extends('layouts.storefront')

@section('title', $product->name . ' - MeiStore')

@section('content')
    {{-- =========================================================
         BREADCRUMB
    ========================================================= --}}
    <div class="store-breadcrumb">
        <div class="container">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
    
                    <li class="breadcrumb-item">
                        <a href="{{ route('storefront.home') }}">
                            <i class="bi bi-house me-1"></i>
                            Home
                        </a>
                    </li>
    
                    <li class="breadcrumb-item">
                        <a href="{{ route('storefront.shop') }}">Shop</a>
                    </li>
    
                    @if ($product->category)
                        <li class="breadcrumb-item">
                            <a href="{{ route('storefront.category', $product->category->slug) }}">{{ $product->category->name }}</a>
                        </li>
                    @endif
    
                    <li class="breadcrumb-item active" aria-current="page">
                        {{ $product->name }}
                    </li>
    
                </ol>
            </nav>
        </div>
    </div>
    
    {{-- =========================================================
         PRODUCT DETAIL
    ========================================================= --}}
    <section class="store-section pt-4 pt-lg-5">
        <div class="container">
            <div class="row g-4 g-lg-5">
                {{-- =================================================
                     PRODUCT IMAGE
                ================================================== --}}
                <div class="col-lg-6">
                    <div class="product-gallery">
                        <div class="product-gallery-main">
                            @if ($product->image)
                                <img src="{{ $product->image }}" alt="{{ $product->name }}" class="product-gallery-image">
                            @else
                                <div class="product-gallery-placeholder">
                                    <i class="bi bi-image"></i>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- =================================================
                     PRODUCT INFORMATION
                ================================================== --}}
                <div class="col-lg-6">
                    {{-- Product name --}}
                    <h1 class="store-product-detail-title">{{ $product->name }}</h1>
                    {{-- Rating --}}
                    <div class="store-product-rating mb-3">
                        @if ($product->reviews_count)
                            <span class="store-rating-stars">
                                @for ($i = 1; $i <= 5; $i++)
                                    <i class="bi bi-star{{ $i <= round($product->reviews_avg_rating) ? '-fill' : '' }}"></i>
                                @endfor
                            </span>
                            <strong class="ms-2">{{ number_format($product->reviews_avg_rating, 1) }}</strong>
                            <a href="#reviews"  class="text-muted ms-1">{{ $product->reviews_count }} review</a>
                        @else
                            <span class="text-muted">Belum ada review</span>
                        @endif
                    </div>

                    {{-- Price --}}
                    <div class="store-product-detail-price">
                        Rp {{ number_format($product->price, 0, ',', '.') }}
                    </div>

                    {{-- Stock --}}
                    <div class="mt-3">
                        @if ($product->stock > 0)
                            <span class="store-stock-badge store-stock-available">
                                <i class="bi bi-check-circle me-1"></i>
                                Stok tersedia
                            </span>
                            <span class="text-muted small ms-2">{{ $product->stock }} unit</span>
                        @else
                            <span class="store-stock-badge store-stock-empty">
                                <i class="bi bi-x-circle me-1"></i>
                                Stok habis
                            </span>
                        @endif
                    </div>

                    {{-- =================================================
                         PURCHASE AREA
                    ================================================== --}}
                    @if ($product->stock > 0)
                        @auth
                            <form action="{{ route('cart.store') }}" method="POST" class="add-to-cart-form mt-4">
                                @csrf
                                <input type="hidden" name="product_id" value="{{ $product->id }}">
    
                                <div class="row g-2">
    
                                    {{-- Quantity --}}
                                    <div class="col-4 col-sm-3">
    
                                        <label for="quantity" class="form-label fw-semibold">Jumlah</label>
                                        <input
                                            type="number"
                                            id="quantity"
                                            name="quantity"
                                            value="1"
                                            min="1"
                                            max="{{ $product->stock }}"
                                            class="form-control product-quantity"
                                            required
                                        >
    
                                    </div>
    
    
                                    {{-- Add cart --}}
                                    <div class="col">
                                        <label class="form-label d-block">&nbsp;</label>
                                        <button
                                            type="submit"
                                            class="store-add-cart add-to-cart-button"
                                        >
                                            <i class="bi bi-cart-plus"></i>
                                            Tambah ke Keranjang
                                        </button>
                                    </div>
                                </div>
                            </form>
                        @else
                            <div class="store-login-box mt-4">
                                <div class="d-flex gap-3">
                                    <div class="store-login-box-icon">
                                        <i class="bi bi-person-lock"></i>
                                    </div>
    
                                    <div>
                                        <strong>Login untuk membeli produk</strong>
                                        <p>Silakan login terlebih dahulu untuk menambahkan produk ke keranjang.</p>
    
                                        <div class="d-flex gap-2 flex-wrap">
                                            <a
                                                href="{{ route('login') }}"
                                                class="store-btn-primary"
                                            >
                                                Login
                                            </a>
    
                                            <a
                                                href="{{ route('register') }}"
                                                class="btn btn-outline-dark"
                                            >
                                                Daftar
                                            </a>
    
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endauth
                    @else
                        <button
                            type="button"
                            class="btn btn-secondary w-100 mt-4"
                            disabled
                        >
                            <i class="bi bi-x-circle me-2"></i>
                            Produk sedang habis
                        </button>
                    @endif
    
                    {{-- =================================================
                         WISHLIST / COMPARE
                    ================================================== --}}
                    @auth
                        <div class="product-secondary-actions mt-3">
    
                            <form
                                action="{{ route(
                                    'wishlist.store',
                                    $product
                                ) }}"
                                method="POST"
                            >
    
                                @csrf
    
                                <button
                                    type="submit"
                                    class="product-secondary-action"
                                >
                                    <i class="bi bi-heart"></i>
                                    Wishlist
                                </button>
    
                            </form>

                            <form
                                action="{{ route(
                                    'compare.store',
                                    $product
                                ) }}"
                                method="POST"
                            >
                                @csrf
                                <button
                                    type="submit"
                                    class="product-secondary-action"
                                >
                                    <i class="bi bi-bar-chart"></i>
                                    Bandingkan
                                </button>
                            </form>
                        </div>
                    @endauth

                </div>
            </div>
        </div>
    </section>

    <section id="reviews" class="store-section store-product-reviews">
        <ul class="nav nav-tabs" id="productTab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link fade show active" id="description-tab" data-bs-toggle="tab" data-bs-target="#description-tab-pane" type="button" role="tab" aria-controls="description-tab-pane" aria-selected="false">Description</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="specification-tab" data-bs-toggle="tab" data-bs-target="#specification-tab-pane" type="button" role="tab" aria-controls="specification-tab-pane" aria-selected="true">Product Spesification</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="review-tab" data-bs-toggle="tab" data-bs-target="#review-tab-pane" type="button" role="tab" aria-controls="review-tab-pane" aria-selected="false">Review</button>
            </li>
        </ul>
        <div class="tab-content" id="TabProduct">
            {{-- DESCRIPTION --}}
            <div class="tab-pane fade" id="description-tab-pane" role="tabpanel" aria-labelledby="description-tab" tabindex="0">
                {{-- Description --}}
                @if ($product->description)
                    <div class="store-product-description mt-4">
                        <h2>Deskripsi Produk</h2>
                        <div>{!! nl2br(e($product->description)) !!}</div>
                    </div>
                @endif

                {{-- Category --}}
                <div class="store-product-detail-category">
                    @if ($product->category)
                        <a href="{{ route('storefront.category', $product->category->slug) }}">{{ $product->category->name }}</a>
                    @else
                        Tanpa kategori
                    @endif
                    @if ($product->subCategory)
                        <span class="mx-1">/</span>
                        {{ $product->subCategory->name }}
                    @endif
                </div>

                {{-- Product information --}}
                <div class="product-meta-list mt-4">
                    <div>
                        <span>Kategori</span>
                        <strong>{{ $product->category?->name ?? '-' }} </strong>
                    </div>
                    @if ($product->subCategory)
                        <div>
                            <span>Subkategori</span>
                            <strong>{{ $product->subCategory->name }}</strong>
                        </div>
                    @endif
                    <div>
                        <span>Ketersediaan</span>
                        <strong>{{ $product->stock > 0 ? 'Tersedia' : 'Habis' }}</strong>
                    </div>
                </div>

            </div>
            {{-- SPECIFICATION --}}
            <div class="tab-pane fade" id="specification-tab-pane" role="tabpanel" aria-labelledby="specification-tab" tabindex="0">
                <section class="store-section store-product-specifications">
                    <div class="container">
                        <div class="store-section-header">
                            <div>
                                <h2 class="store-section-title">Spesifikasi Produk</h2>
                                <p class="store-section-subtitle">
                                    Informasi lengkap mengenai spesifikasi
                                    {{ $product->name }}.
                                </p>
                            </div>
                        </div>
                        @if ($product->specifications->isNotEmpty())
                            @php
                                $specificationGroups = $product->specifications->groupBy('specification_group');
                            @endphp
                           <div class="product-specifications-card">
                                @foreach ($specificationGroups as $groupName => $specifications)
                                    <div class="product-specification-group">
                                        <div class="product-specification-group-header">
                                            <i class="bi bi-list-ul"></i>
                                            <h3>{{ $groupName }}</h3>
                                        </div>
    
                                        <div class="product-specification-table">
                                            @foreach ($specifications as $specification)
                                                <div class="product-specification-row">
                                                    <div class="product-specification-name">
                                                        {{ $specification->specification_name }}
                                                    </div>
    
                                                    <div class="product-specification-value">
                                                        {{ $specification->specification_value }}
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </section>
            </div>
            {{-- REVIEW --}}
            <div class="tab-pane fade" id="review-tab-pane" role="tabpanel" aria-labelledby="review-tab" tabindex="0">
                <div>
                    <div class="store-section-header">
                        <div class="container p-3">
                            <h2 class="store-section-title">Review Produk</h2>
                            <p class="store-section-subtitle">Pengalaman pelanggan setelah membeli produk ini.</p>
                        </div>
                    </div>
                    <div class="review-list">
                        @forelse ($product->reviews as $review)
                            <article class="review-item">
                                <div class="d-flex justify-content-between gap-3">
                                    <div>
                                        <strong class="review-author">{{ $review->user->name }}</strong>
                                        <div class="store-rating-stars mt-1">
                                            @for ($i = 1; $i <= 5; $i++)
                                                <i class="bi bi-star{{ $i <= $review->rating ? '-fill' : '' }}"></i>
                                            @endfor
                                        </div>
                                    </div>
                                    @if ($review->is_verified)
                                        <span class="review-verified">
                                            <i class="bi bi-patch-check-fill me-1"></i>
                                            Verified Purchase
                                        </span>
                                    @endif
                                </div>

                                @if ($review->title)
                                    <h3 class="review-title">{{ $review->title }}</h3>
                                @endif
                                <p class="review-comment">{{ $review->comment }}</p>
                                <small class="review-date">{{ $review->created_at->format('d M Y') }}</small>
                            </article>
                        @empty
                            <div class="review-empty">
                                <i class="bi bi-chat-square-text"></i>
                                <h3>Belum ada review</h3>
                                <p>
                                    Jadilah pelanggan pertama yang
                                    memberikan review untuk produk ini.
                                </p>
                            </div>
                        @endforelse
                    </div>

                    @auth
                        @php
                            $myReview = $product->reviews->firstWhere('user_id', auth()->id());
                            $canReview = auth()->user()->orders()->where('status', 'completed')->whereHas('items', fn ($q) => $q->where('product_id', $product->id))->exists();
                        @endphp

                        @if ($myReview)
                            <div class="review-form-card">
                                <div class="review-form-header">
                                    <div class="container">
                                        <h3>Review Anda</h3>
                                        <p>Anda dapat memperbarui review produk ini.</p>
                                    </div>
                                    <i class="bi bi-pencil-square"></i>
                                </div>

                                <form
                                    action="{{ route(
                                        'reviews.update',
                                        $myReview
                                    ) }}"
                                    method="POST"
                                >

                                    @csrf
                                    @method('PATCH')

                                    <label class="form-label fw-semibold">Rating</label>
                                    <select name="rating" class="form-select mb-3" required>
                                        @for ($i = 5; $i >= 1; $i--)
                                            <option value="{{ $i }}" @selected($myReview->rating == $i)>{{ $i }} Bintang</option>
                                        @endfor
                                    </select>
                                    <label class="form-label fw-semibold">Judul</label>
                                    <input
                                        type="text"
                                        name="title"
                                        class="form-control mb-3"
                                        value="{{ $myReview->title }}"
                                        placeholder="Judul review"
                                    >

                                    <label class="form-label fw-semibold">Review</label>
                                    <textarea
                                        name="comment"
                                        class="form-control mb-3"
                                        rows="4"
                                        required
                                    >{{ $myReview->comment }}</textarea>


                                    <button type="submit" class="store-btn-primary w-100">
                                        <i class="bi bi-check2 me-1"></i>
                                        Update Review
                                    </button>
                                </form>

                                <form action="{{ route('reviews.destroy', $myReview) }}" method="POST" class="mt-2">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-outline-danger btn-sm w-100" onclick="return confirm('Hapus review ini?')">
                                        <i class="bi bi-trash me-1"></i>
                                        Hapus Review
                                    </button>
                                </form>
                            </div>
                        @elseif ($canReview)
                            <div class="review-form-card">
                                <div class="review-form-header">
                                    <div>
                                        <h3>Berikan Review</h3>
                                        <p>Bagikan pengalaman Anda mengenai produk ini.</p>
                                    </div>
                                    <i class="bi bi-star"></i>
                                </div>

                                <form action="{{ route('reviews.store', $product) }}" method="POST">
                                    @csrf
                                    <label class="form-label fw-semibold">
Rating</label>

                                    <select name="rating" class="form-select mb-3" required>

                                        <option value="">Pilih rating</option>

                                        @for ($i = 5; $i >= 1; $i--)
                                            <option value="{{ $i }}">
                                                {{ $i }} Bintang
                                            </option>
                                        @endfor
                                    </select>
                                    <label class="form-label fw-semibold">Judul</label>
                                    <input type="text" name="title" class="form-control mb-3" placeholder="Judul review">

                                    <label class="form-label fw-semibold">Review</label>

                                    <textarea
                                        name="comment"
                                        class="form-control mb-3"
                                        rows="4"
                                        placeholder="Bagaimana pengalaman Anda?"
                                        required
                                    ></textarea>

                                    <button type="submit" class="store-btn-primary w-100">
                                        <i class="bi bi-send me-1"></i>
                                        Kirim Review
                                    </button>
                                </form>
                            </div>
                        @else
                            <div class="review-info-card">
                                <i class="bi bi-info-circle"></i>
                                <div>
                                    <strong>Review hanya untuk pembeli</strong>
                                    <p>Anda dapat memberikan review setelah membeli produk ini dan pesanan berstatus <strong>Completed</strong>.</p>
                                </div>
                            </div>
                        @endif
                    @else
                        <div class="review-info-card">
                            <i class="bi bi-person"></i>
                            <div>
                                <strong>Login untuk memberikan review</strong>
                                <p>Silakan login terlebih dahulu untuk memberikan review </p>
                                <a href="{{ route('login') }}" class="store-btn-primary">Login</a>
                            </div>
                        </div>
                    @endauth
                </div>
            </div>
        </div>
    </section>

    {{-- =========================================================
         REVIEWS
    ========================================================= --}}
    <section id="reviews" class="store-section store-product-reviews">
        <div class="container">
            <div class="row g-4">
    
                {{-- =================================================
                     REVIEW FORM
                ================================================== --}}
                <div class="col-lg-5">
    
                </div>
    
    
                {{-- =================================================
                     REVIEW LIST
                ================================================== --}}
                <div class="col-lg-7">
    
                </div>
            </div>
        </div>
    </section>
    
    {{-- =========================================================
         RELATED PRODUCTS
    ========================================================= --}}
    @if ($relatedProducts->isNotEmpty())
        <section class="store-section mt-2">
            <div class="container">
                <div class="store-section-header">
                    <div>
    
                        <h2 class="store-section-title">
                            Produk Terkait
                        </h2>
    
                        <p class="store-section-subtitle">
                            Produk lain dari kategori yang sama.
                        </p>
    
                    </div>
    
                    @if ($product->category)
    
                        <a
                            href="{{ route(
                                'storefront.category',
                                $product->category->slug
                            ) }}"
                            class="store-section-link"
                        >
                            Lihat semua
                            <i class="bi bi-arrow-right ms-1"></i>
                        </a>
    
                    @endif
    
                </div>
    
    
                <div class="row g-3 g-lg-4">
                    @foreach ($relatedProducts as $relatedProduct)
                        <div class="col-6 col-md-4 col-lg-3">
                            <article class="store-product-card">
                                {{-- Image --}}
                                <div class="store-product-image-wrap">
    
                                    <a
                                        href="{{ route(
                                            'storefront.product',
                                            $relatedProduct->slug
                                        ) }}"
                                    >
    
                                        @if ($relatedProduct->image)
    
                                            <img
                                                src="{{ $relatedProduct->image }}"
                                                alt="{{ $relatedProduct->name }}"
                                                class="store-product-image"
                                                loading="lazy"
                                            >
    
                                        @else
    
                                            <div class="store-product-placeholder">
                                                <i class="bi bi-image"></i>
                                            </div>
    
                                        @endif
    
                                    </a>
    
    
                                    {{-- Actions --}}
                                    @auth
    
                                        <div class="store-product-actions">
    
                                            <form
                                                action="{{ route(
                                                    'wishlist.store',
                                                    $relatedProduct
                                                ) }}"
                                                method="POST"
                                            >
    
                                                @csrf
    
                                                <button
                                                    type="submit"
                                                    class="store-product-action"
                                                    title="Wishlist"
                                                >
                                                    <i class="bi bi-heart"></i>
                                                </button>
    
                                            </form>
    
    
                                            <form
                                                action="{{ route(
                                                    'compare.store',
                                                    $relatedProduct
                                                ) }}"
                                                method="POST"
                                            >
    
                                                @csrf
    
                                                <button
                                                    type="submit"
                                                    class="store-product-action"
                                                    title="Compare"
                                                >
                                                    <i class="bi bi-bar-chart"></i>
                                                </button>
    
                                            </form>
    
                                        </div>
    
                                    @endauth
    
                                </div>
    
    
                                {{-- Body --}}
                                <div class="store-product-body">
    
                                    <div class="store-product-category">
                                        {{ $relatedProduct->category?->name ?? 'Tanpa kategori' }}
                                    </div>
    
    
                                    <a
                                        href="{{ route(
                                            'storefront.product',
                                            $relatedProduct->slug
                                        ) }}"
                                    >
    
                                        <h3 class="store-product-name">
                                            {{ $relatedProduct->name }}
                                        </h3>
    
                                    </a>
    
    
                                    <div class="store-product-price">
                                        Rp {{ number_format(
                                            $relatedProduct->price,
                                            0,
                                            ',',
                                            '.'
                                        ) }}
                                    </div>
    
    
                                    <div class="store-product-stock">
    
                                        @if ($relatedProduct->stock > 0)
    
                                            <i class="bi bi-check-circle text-success"></i>
                                            {{ $relatedProduct->stock }} tersedia
    
                                        @else
    
                                            <i class="bi bi-x-circle text-danger"></i>
                                            Stok habis
    
                                        @endif
    
                                    </div>
    
    
                                    {{-- Cart --}}
                                    @if ($relatedProduct->stock > 0)
    
                                        @auth
    
                                            <form
                                                action="{{ route('cart.store') }}"
                                                method="POST"
                                                class="add-to-cart-form store-product-footer"
                                            >
    
                                                @csrf
    
                                                <input
                                                    type="hidden"
                                                    name="product_id"
                                                    value="{{ $relatedProduct->id }}"
                                                >
    
                                                <input
                                                    type="hidden"
                                                    name="quantity"
                                                    value="1"
                                                >
    
                                                <button
                                                    type="submit"
                                                    class="store-add-cart add-to-cart-button"
                                                >
                                                    <i class="bi bi-cart-plus"></i>
                                                    Tambah
                                                </button>
    
                                            </form>
    
                                        @else
    
                                            <a
                                                href="{{ route('login') }}"
                                                class="store-add-cart store-product-footer"
                                            >
                                                <i class="bi bi-person"></i>
                                                Login untuk membeli
                                            </a>
    
                                        @endauth
    
                                    @else
    
                                        <button
                                            type="button"
                                            class="btn btn-secondary w-100 mt-3"
                                            disabled
                                        >
                                            Stok habis
                                        </button>
    
                                    @endif
    
                                </div>
    
                            </article>
    
                        </div>
    
                    @endforeach
    
                </div>
    
            </div>
    
        </section>
    @endif

@endsection


{{-- =========================================================
     PAGE STYLES
========================================================= --}}
@push('styles')
<style>

    /* =========================================================
       PRODUCT GALLERY
    ========================================================== */
    .product-gallery-main {
        overflow: hidden;

        border: 1px solid var(--store-border);
        border-radius: var(--store-radius);

        background: var(--store-light);
    }

    .product-gallery-image {
        width: 100%;
        height: 560px;

        display: block;

        object-fit: cover;
    }

    .product-gallery-placeholder {
        height: 560px;

        display: flex;
        align-items: center;
        justify-content: center;

        color: #adb5bd;

        font-size: 5rem;
    }


    /* =========================================================
       PRODUCT INFORMATION
    ========================================================== */

    .store-product-detail-category {
        color: var(--store-muted);

        font-size: .8rem;
        font-weight: 700;

        text-transform: uppercase;
        letter-spacing: .04em;
    }

    .store-product-detail-category a {
        color: var(--store-primary-dark);
    }

    .store-product-detail-category a:hover {
        color: var(--store-dark);
    }

    .store-product-detail-title {
        margin: .6rem 0 1rem;

        color: var(--store-dark);

        font-size: clamp(1.8rem, 4vw, 2.8rem);
        font-weight: 900;
        line-height: 1.12;
        letter-spacing: -.035em;
    }

    .store-product-rating {
        display: flex;
        align-items: center;

        flex-wrap: wrap;

        color: var(--store-muted);

        font-size: .86rem;
    }

    .store-rating-stars {
        color: var(--store-primary-dark);
        letter-spacing: 1px;
    }

    .store-product-detail-price {
        color: var(--store-dark);

        font-size: 2rem;
        font-weight: 900;
        letter-spacing: -.025em;
    }


    /* =========================================================
       STOCK
    ========================================================== */

    .store-stock-badge {
        display: inline-flex;
        align-items: center;

        padding: .45rem .75rem;

        border-radius: 4px;

        font-size: .8rem;
        font-weight: 700;
    }

    .store-stock-available {
        background: #eaf7ef;
        color: #198754;
    }

    .store-stock-empty {
        background: #fcebea;
        color: var(--store-danger);
    }


    /* =========================================================
       DESCRIPTION
    ========================================================== */

    .store-product-description {
        padding-top: 1.25rem;

        border-top: 1px solid var(--store-border);
    }

    .store-product-description h2 {
        margin-bottom: .75rem;

        color: var(--store-dark);

        font-size: 1rem;
        font-weight: 800;
    }

    .store-product-description > div {
        color: var(--store-muted);

        line-height: 1.8;
    }


    /* =========================================================
       LOGIN BOX
    ========================================================== */

    .store-login-box {
        padding: 1rem;

        border: 1px solid var(--store-border);
        border-radius: var(--store-radius);

        background: var(--store-light);
    }

    .store-login-box-icon {
        width: 42px;
        height: 42px;

        flex: 0 0 auto;

        display: flex;
        align-items: center;
        justify-content: center;

        border-radius: 50%;

        background: #fff7d6;
        color: var(--store-primary-dark);

        font-size: 1.2rem;
    }

    .store-login-box strong {
        color: var(--store-dark);
    }

    .store-login-box p {
        margin: .35rem 0 .8rem;

        color: var(--store-muted);

        font-size: .85rem;
    }


    /* =========================================================
       SECONDARY ACTIONS
    ========================================================== */

    .product-secondary-actions {
        display: flex;
        flex-wrap: wrap;
        gap: .65rem;
    }

    .product-secondary-actions form {
        flex: 1 1 180px;
    }

    .product-secondary-action {
        width: 100%;

        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: .45rem;

        padding: .65rem .8rem;

        border: 1px solid var(--store-border);
        border-radius: 4px;

        background: #fff;
        color: var(--store-dark);

        font-size: .84rem;
        font-weight: 700;
    }

    .product-secondary-action:hover {
        border-color: var(--store-primary);
        background: #fffaf0;
    }


    /* =========================================================
       PRODUCT META
    ========================================================== */

    .product-meta-list {
        border-top: 1px solid var(--store-border);
    }

    .product-meta-list > div {
        display: flex;
        justify-content: space-between;
        gap: 1rem;

        padding: .75rem 0;

        border-bottom: 1px solid var(--store-border);

        font-size: .84rem;
    }

    .product-meta-list span {
        color: var(--store-muted);
    }

    .product-meta-list strong {
        color: var(--store-dark);
        text-align: right;
    }


    /* =========================================================
       REVIEW
    ========================================================== */

    .store-product-reviews {
        background: var(--store-light);
    }

    .review-form-card {
        padding: 1.25rem;

        border: 1px solid var(--store-border);
        border-radius: var(--store-radius);

        background: #fff;
    }

    .review-form-header {
        display: flex;
        justify-content: space-between;
        gap: 1rem;

        margin-bottom: 1.25rem;
    }

    .review-form-header h3 {
        margin: 0;

        color: var(--store-dark);

        font-size: 1rem;
        font-weight: 800;
    }

    .review-form-header p {
        margin: .3rem 0 0;

        color: var(--store-muted);

        font-size: .8rem;
    }

    .review-form-header > i {
        color: var(--store-primary-dark);

        font-size: 1.5rem;
    }

    .review-info-card {
        display: flex;
        gap: 1rem;

        padding: 1.25rem;

        border: 1px solid var(--store-border);
        border-radius: var(--store-radius);

        background: #fff;
    }

    .review-info-card > i {
        color: var(--store-primary-dark);
        font-size: 1.5rem;
    }

    .review-info-card strong {
        color: var(--store-dark);
    }

    .review-info-card p {
        margin: .4rem 0 1rem;

        color: var(--store-muted);

        font-size: .84rem;
        line-height: 1.6;
    }

    .review-list {
        overflow: hidden;

        border: 1px solid var(--store-border);
        border-radius: var(--store-radius);

        background: #fff;
    }

    .review-item {
        padding: 1.25rem;

        border-bottom: 1px solid var(--store-border);
    }

    .review-item:last-child {
        border-bottom: 0;
    }

    .review-author {
        color: var(--store-dark);

        font-size: .9rem;
    }

    .review-verified {
        color: #198754;

        font-size: .72rem;
        font-weight: 700;
        white-space: nowrap;
    }

    .review-title {
        margin: .8rem 0 .35rem;

        color: var(--store-dark);

        font-size: .95rem;
        font-weight: 800;
    }

    .review-comment {
        margin: .4rem 0;

        color: #555;

        font-size: .86rem;
        line-height: 1.7;
    }

    .review-date {
        color: var(--store-muted);
    }

    .review-empty {
        padding: 3rem 1.5rem;

        color: var(--store-muted);

        text-align: center;
    }

    .review-empty > i {
        font-size: 2.5rem;
    }

    .review-empty h3 {
        margin: 1rem 0 .35rem;

        color: var(--store-dark);

        font-size: 1rem;
    }

    .review-empty p {
        margin: 0;

        font-size: .84rem;
    }


    /* =========================================================
       RESPONSIVE
    ========================================================== */

    @media (max-width: 991.98px) {

        .product-gallery-image,
        .product-gallery-placeholder {
            height: 450px;
        }

    }

    @media (max-width: 767.98px) {

        .product-gallery-image,
        .product-gallery-placeholder {
            height: 330px;
        }

        .store-product-detail-title {
            font-size: 1.8rem;
        }

        .store-product-detail-price {
            font-size: 1.6rem;
        }

        .product-meta-list > div {
            align-items: flex-start;
        }

        .review-item {
            padding: 1rem;
        }

        .review-verified {
            font-size: .65rem;
        }

    }

/* =========================================================
   PRODUCT SPECIFICATIONS
========================================================= */

.store-product-specifications {
    padding-top: 1rem;
}

.product-specifications-card {
    overflow: hidden;

    border: 1px solid var(--store-border);
    border-radius: var(--store-radius);

    background: var(--store-white);
}


/* =========================================================
   SPECIFICATION GROUP
========================================================= */

.product-specification-group {
    border-bottom: 1px solid var(--store-border);
}

.product-specification-group:last-child {
    border-bottom: 0;
}


/* =========================================================
   GROUP HEADER
========================================================= */

.product-specification-group-header {
    display: flex;
    align-items: center;
    gap: .65rem;

    padding: 1rem 1.25rem;

    background: var(--store-light);

    border-bottom: 1px solid var(--store-border);
}

.product-specification-group-header i {
    color: var(--store-primary-dark);

    font-size: 1rem;
}

.product-specification-group-header h3 {
    margin: 0;

    color: var(--store-dark);

    font-size: .95rem;
    font-weight: 800;
}


/* =========================================================
   SPECIFICATION ROW
========================================================= */

.product-specification-row {
    display: grid;
    grid-template-columns: minmax(180px, 30%) 1fr;

    border-bottom: 1px solid var(--store-border);
}

.product-specification-row:last-child {
    border-bottom: 0;
}


/* =========================================================
   SPECIFICATION NAME
========================================================= */

.product-specification-name {
    padding: .9rem 1.25rem;

    background: #fafafa;

    color: var(--store-muted);

    font-size: .86rem;
    font-weight: 600;
}


/* =========================================================
   SPECIFICATION VALUE
========================================================= */

.product-specification-value {
    padding: .9rem 1.25rem;

    color: var(--store-dark);

    font-size: .88rem;
    line-height: 1.6;
}


/* =========================================================
   HOVER
========================================================= */

.product-specification-row:hover
.product-specification-name {
    color: var(--store-dark);

    background: #fff9e6;
}

.product-specification-row:hover
.product-specification-value {
    background: #fffdf5;
}


/* =========================================================
   MOBILE
========================================================= */

@media (max-width: 767.98px) {

    .store-product-specifications {
        padding-top: 0;
    }

    .product-specification-group-header {
        padding: .9rem 1rem;
    }

    .product-specification-row {
        grid-template-columns: 1fr;
    }

    .product-specification-name {
        padding: .7rem 1rem;

        background: #fafafa;

        font-size: .78rem;
    }

    .product-specification-value {
        padding: .7rem 1rem .9rem;

        font-size: .84rem;
    }

}
</style>
@endpush


{{-- =========================================================
     PAGE SCRIPTS
========================================================= --}}
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const forms = document.querySelectorAll('.add-to-cart-form');
    forms.forEach(function (form) {
        form.addEventListener('submit', async function (event) {
            event.preventDefault();
            const button = form.querySelector('.add-to-cart-button');
            if (!button) {
                return;
            }

            const originalHtml = button.innerHTML;
            button.disabled = true;
            button.innerHTML = `
                <span
                    class="spinner-border spinner-border-sm me-1"
                    role="status"
                    aria-hidden="true"
                ></span>
                Menambahkan...
            `;

            try {

                const response = await fetch(
                    form.action,
                    {
                        method: 'POST',

                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN':
                                document
                                    .querySelector(
                                        'meta[name="csrf-token"]'
                                    )
                                    .getAttribute('content')
                        },

                        body: new FormData(form)
                    }
                );

                const data =
                    await response.json();

                if (
                    !response.ok ||
                    !data.success
                ) {
                    throw new Error(
                        data.message ||
                        'Gagal menambahkan produk ke keranjang.'
                    );
                }


                /*
                 * Use global storefront helper
                 * provided by layouts.storefront.
                 */
                if (
                    typeof window.updateCartBadge ===
                    'function'
                ) {
                    window.updateCartBadge(
                        data.cart_count
                    );
                }


                if (
                    typeof window.showStoreNotification ===
                    'function'
                ) {
                    window.showStoreNotification(
                        data.message ||
                        'Produk berhasil ditambahkan ke keranjang.',
                        'success'
                    );
                }

            } catch (error) {

                if (
                    typeof window.showStoreNotification ===
                    'function'
                ) {
                    window.showStoreNotification(
                        error.message ||
                        'Terjadi kesalahan.',
                        'danger'
                    );
                }

            } finally {

                button.disabled = false;
                button.innerHTML = originalHtml;

            }

        });

    });

});
</script>
@endpush

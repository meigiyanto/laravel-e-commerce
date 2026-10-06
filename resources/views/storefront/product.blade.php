@extends('layouts.storefront')

@section('title', $product->name . ' - MeiStore')

@section('content')

    {{-- =========================================================
         BREADCRUMB
    ========================================================== --}}
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
        </div>
    </div>


    {{-- =========================================================
         PRODUCT DETAIL
    ========================================================== --}}
    <section class="product-detail-section">
        <div class="container">

            <div class="row g-4 g-xl-5">

                {{-- =================================================
                     PRODUCT IMAGE
                ================================================== --}}
                <div class="col-lg-6">
                    <div class="product-gallery">

                        <div class="product-gallery-main">

                            @if ($product->image_url)
                                <img
                                    src="{{ $product->image_url }}"
                                    alt="{{ $product->name }}"
                                    class="product-gallery-image"
                                >
                            @else
                                <div class="product-gallery-placeholder">
                                    <i class="bi bi-image"></i>
                                    <span>Gambar tidak tersedia</span>
                                </div>
                            @endif

                        </div>

                    </div>
                </div>


                {{-- =================================================
                     PRODUCT INFORMATION
                ================================================== --}}
                <div class="col-lg-6">

                    <div class="product-detail-info">

                        {{-- Category --}}
                        @if ($product->category)
                            <div class="product-detail-category">
                                <a href="{{ route('storefront.category', $product->category->slug) }}">
                                    {{ $product->category->name }}
                                </a>

                                @if ($product->subCategory)
                                    <span>/</span>
                                    <span>{{ $product->subCategory->name }}</span>
                                @endif
                            </div>
                        @endif


                        {{-- Product name --}}
                        <h1 class="product-detail-title">
                            {{ $product->name }}
                        </h1>


                        {{-- Rating --}}
                        <div class="product-detail-rating">

                            @if ($product->reviews_count > 0)

                                <span class="rating-stars">
                                    @for ($i = 1; $i <= 5; $i++)
                                        <i class="bi bi-star{{ $i <= round($product->reviews_avg_rating) ? '-fill' : '' }}"></i>
                                    @endfor
                                </span>

                                <strong>
                                    {{ number_format($product->reviews_avg_rating, 1) }}
                                </strong>

                                <a href="#product-reviews">
                                    {{ $product->reviews_count }}
                                    {{ $product->reviews_count == 1 ? 'Review' : 'Reviews' }}
                                </a>

                            @else

                                <span class="text-muted">
                                    Belum ada review
                                </span>

                            @endif

                        </div>


                        {{-- Price --}}
                        <div class="product-detail-price">
                            Rp {{ number_format($product->price, 0, ',', '.') }}
                        </div>


                        {{-- Stock --}}
                        <div class="product-stock">

                            @if ($product->stock > 0)

                                <span class="stock-status stock-available">
                                    <i class="bi bi-check-circle-fill"></i>
                                    Stok tersedia
                                </span>

                                <span class="stock-count">
                                    {{ $product->stock }} unit tersedia
                                </span>

                            @else

                                <span class="stock-status stock-empty">
                                    <i class="bi bi-x-circle-fill"></i>
                                    Stok habis
                                </span>

                            @endif

                        </div>


                        {{-- Short description --}}
                        @if ($product->description)
                            <div class="product-detail-summary">
                                {{ \Illuminate\Support\Str::limit($product->description, 220) }}
                            </div>
                        @endif


                        {{-- =================================================
                             PURCHASE
                        ================================================== --}}
                        @if ($product->stock > 0)
                            @auth
                                <form action="{{ route('cart.store') }}" method="POST" class="product-purchase-form add-to-cart-form">
                                    @csrf
                                    <input
                                        type="hidden"
                                        name="product_id"
                                        value="{{ $product->id }}"
                                    >

                                    <div class="purchase-row">

                                        <div class="quantity-control">
                                            <label for="quantity">
                                                Jumlah
                                            </label>

                                            <input
                                                type="number"
                                                id="quantity"
                                                name="quantity"
                                                value="1"
                                                min="1"
                                                max="{{ $product->stock }}"
                                                required
                                            >
                                        </div>

                                        <div class="purchase-button-wrap">
                                            <label class="d-none d-sm-block">
                                                &nbsp;
                                            </label>

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
                                <div class="product-login-box">
                                    <div class="product-login-icon">
                                        <i class="bi bi-person-lock"></i>
                                    </div>
                                    <div>
                                        <strong>
                                            Login untuk membeli produk
                                        </strong>

                                        <p>
                                            Silakan login terlebih dahulu
                                            untuk menambahkan produk ke
                                            keranjang.
                                        </p>

                                        <div class="product-login-actions">

                                            <a
                                                href="{{ route('login') }}"
                                                class="store-btn-primary"
                                            >
                                                Login
                                            </a>

                                            <a
                                                href="{{ route('register') }}"
                                                class="store-btn-outline"
                                            >
                                                Daftar
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            @endauth
                        @else

                            <div class="product-out-of-stock">
                                <i class="bi bi-x-circle"></i>
                                Produk sedang habis
                            </div>

                        @endif


                        {{-- =================================================
                             WISHLIST / COMPARE
                        ================================================== --}}
                        @auth
                            <div class="product-secondary-actions">
                                <form action="{{ route('wishlist.store', $product) }}" method="POST" class="wishlist-form">
                                    @csrf
                                    <button type="submit" class="product-secondary-action">
                                        <i class="bi bi-heart"></i>
                                        Wishlist
                                    </button>
                                </form>

                                <form action="{{ route('compare.store', $product) }}" method="POST" class="compare-form">

                                    @csrf
                                    <button type="submit" class="product-secondary-action">
                                        <i class="bi bi-bar-chart"></i>
                                        Bandingkan
                                    </button>
                                </form>

                            </div>
                        @endauth


                        {{-- Product trust information --}}
                        <div class="product-trust-list">
                            <div>
                                <i class="bi bi-shield-check"></i>
                                <span>Trusted Product</span>
                            </div>
                            <div>
                                <i class="bi bi-box-seam"></i>
                                <span>Stok diperbarui secara berkala</span>
                            </div>
                            <div>
                                <i class="bi bi-headset"></i>
                                <span>Dukungan pelanggan</span>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </section>


    {{-- =========================================================
         PRODUCT INFORMATION
    ========================================================== --}}
    <section class="product-information-section">
        <div class="container">
            <div class="product-tabs-card">

                {{-- Tabs --}}
                <ul
                    class="nav nav-tabs product-tabs"
                    id="productTab"
                    role="tablist"
                >

                    <li class="nav-item" role="presentation">
                        <button
                            class="nav-link active"
                            id="description-tab"
                            data-bs-toggle="tab"
                            data-bs-target="#description-pane"
                            type="button"
                            role="tab"
                            aria-controls="description-pane"
                            aria-selected="true"
                        >
                            Deskripsi
                        </button>
                    </li>

                    <li class="nav-item" role="presentation">
                        <button
                            class="nav-link"
                            id="specification-tab"
                            data-bs-toggle="tab"
                            data-bs-target="#specification-pane"
                            type="button"
                            role="tab"
                            aria-controls="specification-pane"
                            aria-selected="false"
                        >
                            Spesifikasi
                        </button>
                    </li>

                    <li class="nav-item" role="presentation">
                        <button
                            class="nav-link"
                            id="review-tab"
                            data-bs-toggle="tab"
                            data-bs-target="#review-pane"
                            type="button"
                            role="tab"
                            aria-controls="review-pane"
                            aria-selected="false"
                        >
                            Review
                            @if ($product->reviews_count > 0)
                                <span class="tab-count">
                                    {{ $product->reviews_count }}
                                </span>
                            @endif
                        </button>
                    </li>

                </ul>


                {{-- Tab content --}}
                <div class="tab-content product-tab-content">

                    {{-- =================================================
                         DESCRIPTION
                    ================================================== --}}
                    <div
                        class="tab-pane fade show active"
                        id="description-pane"
                        role="tabpanel"
                        aria-labelledby="description-tab"
                        tabindex="0"
                    >

                        <div class="product-content-header">
                            <h2>
                                Deskripsi Produk
                            </h2>

                            <p>
                                Informasi mengenai {{ $product->name }}.
                            </p>
                        </div>


                        @if ($product->description)

                            <div class="product-description">
                                {!! nl2br(e($product->description)) !!}
                            </div>

                        @else

                            <div class="product-empty-content">
                                <i class="bi bi-file-text"></i>

                                <p>
                                    Belum ada deskripsi untuk produk ini.
                                </p>
                            </div>

                        @endif


                        <div class="product-meta-list">

                            <div class="product-meta-item">
                                <span>Kategori</span>

                                <strong>
                                    {{ $product->category?->name ?? '-' }}
                                </strong>
                            </div>

                            <div class="product-meta-item">
                                <span>Subkategori</span>

                                <strong>
                                    {{ $product->subCategory?->name ?? '-' }}
                                </strong>
                            </div>

                            <div class="product-meta-item">
                                <span>Ketersediaan</span>

                                <strong>
                                    {{ $product->stock > 0 ? 'Tersedia' : 'Habis' }}
                                </strong>
                            </div>

                            <div class="product-meta-item">
                                <span>Stok</span>

                                <strong>
                                    {{ $product->stock }} unit
                                </strong>
                            </div>

                        </div>

                    </div>


                    {{-- =================================================
                         SPECIFICATION
                    ================================================== --}}
                    <div
                        class="tab-pane fade"
                        id="specification-pane"
                        role="tabpanel"
                        aria-labelledby="specification-tab"
                        tabindex="0"
                    >

                        <div class="product-content-header">
                            <h2>
                                Spesifikasi Produk
                            </h2>

                            <p>
                                Informasi teknis dan spesifikasi
                                {{ $product->name }}.
                            </p>
                        </div>


                        @if ($product->specifications->isNotEmpty())

                            @php
                                $specificationGroups = $product->specifications
                                    ->groupBy('specification_group');
                            @endphp

                            <div class="product-specifications">

                                @foreach ($specificationGroups as $groupName => $specifications)

                                    <div class="specification-group">

                                        <div class="specification-group-header">
                                            <i class="bi bi-list-check"></i>

                                            <h3>
                                                {{ $groupName }}
                                            </h3>
                                        </div>

                                        <div class="specification-rows">

                                            @foreach ($specifications as $specification)

                                                <div class="specification-row">

                                                    <div class="specification-name">
                                                        {{ $specification->specification_name }}
                                                    </div>

                                                    <div class="specification-value">
                                                        {{ $specification->specification_value }}
                                                    </div>

                                                </div>

                                            @endforeach

                                        </div>

                                    </div>

                                @endforeach

                            </div>

                        @else

                            <div class="product-empty-content">
                                <i class="bi bi-card-list"></i>

                                <p>
                                    Belum ada spesifikasi untuk produk ini.
                                </p>
                            </div>

                        @endif

                    </div>


                    {{-- =================================================
                         REVIEWS
                    ================================================== --}}
                    <div
                        class="tab-pane fade"
                        id="review-pane"
                        role="tabpanel"
                        aria-labelledby="review-tab"
                        tabindex="0"
                    >

                        <div id="product-reviews">

                            <div class="product-content-header">
                                <h2>
                                    Review Produk
                                </h2>

                                <p>
                                    Pengalaman pelanggan setelah membeli
                                    produk ini.
                                </p>
                            </div>


                            {{-- Rating summary --}}
                            @if ($product->reviews_count > 0)

                                <div class="review-summary">

                                    <div class="review-summary-score">
                                        <strong>
                                            {{ number_format($product->reviews_avg_rating, 1) }}
                                        </strong>

                                        <div class="rating-stars">
                                            @for ($i = 1; $i <= 5; $i++)
                                                <i class="bi bi-star{{ $i <= round($product->reviews_avg_rating) ? '-fill' : '' }}"></i>
                                            @endfor
                                        </div>

                                        <span>
                                            {{ $product->reviews_count }}
                                            {{ $product->reviews_count == 1 ? 'review' : 'reviews' }}
                                        </span>
                                    </div>

                                    <div class="review-summary-description">
                                        <strong>
                                            Penilaian pelanggan
                                        </strong>

                                        <p>
                                            Rating berdasarkan review
                                            pelanggan yang telah membeli
                                            produk ini.
                                        </p>
                                    </div>

                                </div>

                            @endif


                            <div class="reviews-layout">

                                {{-- Review list --}}
                                <div class="review-list">

                                    @forelse ($product->reviews as $review)

                                        <article class="review-item">

                                            <div class="review-item-header">

                                                <div>
                                                    <strong class="review-author">
                                                        {{ $review->user->name }}
                                                    </strong>

                                                    <div class="rating-stars">
                                                        @for ($i = 1; $i <= 5; $i++)
                                                            <i class="bi bi-star{{ $i <= $review->rating ? '-fill' : '' }}"></i>
                                                        @endfor
                                                    </div>
                                                </div>

                                                @if ($review->is_verified)
                                                    <span class="review-verified">
                                                        <i class="bi bi-patch-check-fill"></i>
                                                        Verified Purchase
                                                    </span>
                                                @endif

                                            </div>


                                            @if ($review->title)
                                                <h3 class="review-title">
                                                    {{ $review->title }}
                                                </h3>
                                            @endif


                                            @if ($review->comment)
                                                <p class="review-comment">
                                                    {{ $review->comment }}
                                                </p>
                                            @endif


                                            <time class="review-date">
                                                {{ $review->created_at->format('d M Y') }}
                                            </time>

                                        </article>

                                    @empty

                                        <div class="review-empty">
                                            <i class="bi bi-chat-square-text"></i>

                                            <h3>
                                                Belum ada review
                                            </h3>

                                            <p>
                                                Belum ada pelanggan yang
                                                memberikan review untuk
                                                produk ini.
                                            </p>
                                        </div>

                                    @endforelse

                                </div>


                                {{-- Review form --}}
                                @auth

                                    @php
                                        $myReview = $product->reviews
                                            ->firstWhere('user_id', auth()->id());

                                        $canReview = auth()->user()
                                            ->orders()
                                            ->where('status', 'completed')
                                            ->whereHas(
                                                'items',
                                                fn ($q) => $q->where(
                                                    'product_id',
                                                    $product->id
                                                )
                                            )
                                            ->exists();
                                    @endphp


                                    <div class="review-form-column">

                                        @if ($myReview)

                                            <div class="review-form-card">

                                                <div class="review-form-heading">
                                                    <div>
                                                        <h3>
                                                            Review Anda
                                                        </h3>

                                                        <p>
                                                            Anda dapat
                                                            memperbarui
                                                            review produk ini.
                                                        </p>
                                                    </div>

                                                    <i class="bi bi-pencil-square"></i>
                                                </div>


                                                <form
                                                    action="{{ route('reviews.update', $myReview) }}"
                                                    method="POST"
                                                >
                                                    @csrf
                                                    @method('PATCH')

                                                    <div class="review-form-field">

                                                        <label for="review-rating">
                                                            Rating
                                                        </label>

                                                        <select
                                                            id="review-rating"
                                                            name="rating"
                                                            class="form-select"
                                                            required
                                                        >
                                                            @for ($i = 5; $i >= 1; $i--)
                                                                <option
                                                                    value="{{ $i }}"
                                                                    @selected($myReview->rating == $i)
                                                                >
                                                                    {{ $i }} Bintang
                                                                </option>
                                                            @endfor
                                                        </select>

                                                    </div>


                                                    <div class="review-form-field">

                                                        <label for="review-title">
                                                            Judul
                                                        </label>

                                                        <input
                                                            type="text"
                                                            id="review-title"
                                                            name="title"
                                                            class="form-control"
                                                            value="{{ $myReview->title }}"
                                                            placeholder="Judul review"
                                                        >

                                                    </div>


                                                    <div class="review-form-field">

                                                        <label for="review-comment">
                                                            Review
                                                        </label>

                                                        <textarea
                                                            id="review-comment"
                                                            name="comment"
                                                            class="form-control"
                                                            rows="5"
                                                            required
                                                        >{{ $myReview->comment }}</textarea>

                                                    </div>


                                                    <button
                                                        type="submit"
                                                        class="store-btn-primary w-100"
                                                    >
                                                        <i class="bi bi-check2"></i>
                                                        Update Review
                                                    </button>

                                                </form>


                                                <form
                                                    action="{{ route('reviews.destroy', $myReview) }}"
                                                    method="POST"
                                                    class="mt-2"
                                                >
                                                    @csrf
                                                    @method('DELETE')

                                                    <button
                                                        type="submit"
                                                        class="review-delete-button"
                                                        onclick="return confirm('Hapus review ini?')"
                                                    >
                                                        <i class="bi bi-trash"></i>
                                                        Hapus Review
                                                    </button>

                                                </form>

                                            </div>

                                        @elseif ($canReview)

                                            <div class="review-form-card">

                                                <div class="review-form-heading">
                                                    <div>
                                                        <h3>
                                                            Berikan Review
                                                        </h3>

                                                        <p>
                                                            Bagikan pengalaman
                                                            Anda mengenai
                                                            produk ini.
                                                        </p>
                                                    </div>

                                                    <i class="bi bi-star"></i>
                                                </div>


                                                <form
                                                    action="{{ route('reviews.store', $product) }}"
                                                    method="POST"
                                                >
                                                    @csrf

                                                    <div class="review-form-field">

                                                        <label for="new-review-rating">
                                                            Rating
                                                        </label>

                                                        <select
                                                            id="new-review-rating"
                                                            name="rating"
                                                            class="form-select"
                                                            required
                                                        >
                                                            <option value="">
                                                                Pilih rating
                                                            </option>

                                                            @for ($i = 5; $i >= 1; $i--)
                                                                <option value="{{ $i }}">
                                                                    {{ $i }} Bintang
                                                                </option>
                                                            @endfor
                                                        </select>

                                                    </div>


                                                    <div class="review-form-field">

                                                        <label for="new-review-title">
                                                            Judul
                                                        </label>

                                                        <input
                                                            type="text"
                                                            id="new-review-title"
                                                            name="title"
                                                            class="form-control"
                                                            placeholder="Judul review"
                                                        >

                                                    </div>


                                                    <div class="review-form-field">

                                                        <label for="new-review-comment">
                                                            Review
                                                        </label>

                                                        <textarea
                                                            id="new-review-comment"
                                                            name="comment"
                                                            class="form-control"
                                                            rows="5"
                                                            placeholder="Bagaimana pengalaman Anda?"
                                                            required
                                                        ></textarea>

                                                    </div>


                                                    <button
                                                        type="submit"
                                                        class="store-btn-primary w-100"
                                                    >
                                                        <i class="bi bi-send"></i>
                                                        Kirim Review
                                                    </button>

                                                </form>

                                            </div>

                                        @else

                                            <div class="review-info-card">

                                                <i class="bi bi-info-circle"></i>

                                                <div>
                                                    <strong>
                                                        Review hanya untuk pembeli
                                                    </strong>

                                                    <p>
                                                        Anda dapat memberikan
                                                        review setelah membeli
                                                        produk ini dan pesanan
                                                        berstatus
                                                        <strong>Completed</strong>.
                                                    </p>
                                                </div>

                                            </div>

                                        @endif

                                    </div>

                                @else

                                    <div class="review-form-column">

                                        <div class="review-info-card">

                                            <i class="bi bi-person"></i>

                                            <div>
                                                <strong>
                                                    Login untuk memberikan review
                                                </strong>

                                                <p>
                                                    Silakan login terlebih dahulu
                                                    untuk memberikan review
                                                    produk ini.
                                                </p>

                                                <a
                                                    href="{{ route('login') }}"
                                                    class="store-btn-primary"
                                                >
                                                    Login
                                                </a>
                                            </div>

                                        </div>

                                    </div>

                                @endauth

                            </div>

                        </div>

                    </div>

                </div>

            </div>

        </div>
    </section>


    {{-- =========================================================
         RELATED PRODUCTS
    ========================================================== --}}
    @if ($relatedProducts->isNotEmpty())
        <section class="store-section related-products-section">
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
                            href="{{ route('storefront.category', $product->category->slug) }}"
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
                            <x-store-product-card :product="$relatedProduct" />
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
       PRODUCT DETAIL
    ========================================================== */

    .product-detail-section {
        padding: 3rem 0 4rem;
        background: #fff;
    }

    .product-gallery-main {
        overflow: hidden;
        border: 1px solid var(--store-border);
        border-radius: var(--store-radius);
        background: var(--store-light);
    }

    .product-gallery-image,
    .product-gallery-placeholder {
        width: 100%;
        height: 560px;
    }

    .product-gallery-image {
        display: block;
        object-fit: cover;
    }

    .product-gallery-placeholder {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: .5rem;
        color: #adb5bd;
        font-size: 4rem;
    }

    .product-gallery-placeholder span {
        font-size: .8rem;
    }


    /* =========================================================
       PRODUCT INFORMATION
    ========================================================== */

    .product-detail-info {
        height: 100%;
        display: flex;
        flex-direction: column;
    }

    .product-detail-category {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: .45rem;
        margin-bottom: .65rem;
        color: var(--store-muted);
        font-size: .78rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .045em;
    }

    .product-detail-category a {
        color: var(--store-primary-dark);
        text-decoration: none;
    }

    .product-detail-category a:hover {
        color: var(--store-dark);
    }

    .product-detail-title {
        margin: 0 0 1rem;
        color: var(--store-dark);
        font-size: clamp(1.8rem, 4vw, 2.8rem);
        font-weight: 900;
        line-height: 1.12;
        letter-spacing: -.035em;
    }

    .product-detail-rating {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: .5rem;
        margin-bottom: 1.25rem;
        color: var(--store-muted);
        font-size: .85rem;
    }

    .product-detail-rating a {
        color: var(--store-muted);
        text-decoration: underline;
        text-underline-offset: 2px;
    }

    .rating-stars {
        color: var(--store-primary-dark);
        letter-spacing: 1px;
        white-space: nowrap;
    }

    .product-detail-price {
        margin-bottom: 1rem;
        color: var(--store-dark);
        font-size: 2.1rem;
        font-weight: 900;
        letter-spacing: -.03em;
    }


    /* =========================================================
       STOCK
    ========================================================== */

    .product-stock {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: .65rem;
        margin-bottom: 1.25rem;
    }

    .stock-status {
        display: inline-flex;
        align-items: center;
        gap: .4rem;
        padding: .4rem .7rem;
        border-radius: 4px;
        font-size: .78rem;
        font-weight: 700;
    }

    .stock-available {
        background: #eaf7ef;
        color: #198754;
    }

    .stock-empty {
        background: #fcebea;
        color: var(--store-danger);
    }

    .stock-count {
        color: var(--store-muted);
        font-size: .8rem;
    }

    .product-detail-summary {
        padding: 1rem 0;
        border-top: 1px solid var(--store-border);
        border-bottom: 1px solid var(--store-border);
        color: var(--store-muted);
        font-size: .88rem;
        line-height: 1.75;
    }


    /* =========================================================
       PURCHASE
    ========================================================== */

    .product-purchase-form {
        margin-top: 1.5rem;
    }

    .purchase-row {
        display: flex;
        align-items: flex-end;
        gap: .75rem;
    }

    .quantity-control {
        width: 110px;
        flex: 0 0 110px;
    }

    .quantity-control label,
    .purchase-button-wrap label {
        display: block;
        margin-bottom: .4rem;
        color: var(--store-dark);
        font-size: .78rem;
        font-weight: 700;
    }

    .quantity-control input {
        width: 100%;
        min-height: 46px;
        border: 1px solid var(--store-border);
        border-radius: 4px;
        padding: .65rem .75rem;
        color: var(--store-dark);
        background: #fff;
        outline: none;
    }

    .quantity-control input:focus {
        border-color: var(--store-primary);
        box-shadow: 0 0 0 3px rgba(255, 193, 7, .12);
    }

    .purchase-button-wrap {
        flex: 1;
    }

    .purchase-button-wrap .store-add-cart {
        min-height: 46px;
    }

    .product-out-of-stock {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: .5rem;
        width: 100%;
        min-height: 46px;
        margin-top: 1.5rem;
        border-radius: 4px;
        background: #6c757d;
        color: #fff;
        font-size: .85rem;
        font-weight: 700;
    }


    /* =========================================================
       LOGIN
    ========================================================== */

    .product-login-box {
        display: flex;
        gap: 1rem;
        margin-top: 1.5rem;
        padding: 1rem;
        border: 1px solid var(--store-border);
        border-radius: var(--store-radius);
        background: var(--store-light);
    }

    .product-login-icon {
        width: 42px;
        height: 42px;
        flex: 0 0 auto;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        background: #fff7d6;
        color: var(--store-primary-dark);
        font-size: 1.15rem;
    }

    .product-login-box strong {
        color: var(--store-dark);
        font-size: .9rem;
    }

    .product-login-box p {
        margin: .3rem 0 .8rem;
        color: var(--store-muted);
        font-size: .8rem;
        line-height: 1.6;
    }

    .product-login-actions {
        display: flex;
        flex-wrap: wrap;
        gap: .5rem;
    }

    .store-btn-outline {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 38px;
        padding: .5rem .9rem;
        border: 1px solid var(--store-border);
        border-radius: 4px;
        background: #fff;
        color: var(--store-dark);
        font-size: .8rem;
        font-weight: 700;
        text-decoration: none;
    }

    .store-btn-outline:hover {
        border-color: var(--store-dark);
        color: var(--store-dark);
    }


    /* =========================================================
       SECONDARY ACTIONS
    ========================================================== */

    .product-secondary-actions {
        display: flex;
        gap: .65rem;
        margin-top: .85rem;
    }

    .product-secondary-actions form {
        flex: 1;
    }

    .product-secondary-action {
        width: 100%;
        min-height: 42px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: .45rem;
        border: 1px solid var(--store-border);
        border-radius: 4px;
        background: #fff;
        color: var(--store-dark);
        font-size: .8rem;
        font-weight: 700;
    }

    .product-secondary-action:hover {
        border-color: var(--store-primary);
        background: #fffaf0;
    }


    /* =========================================================
       TRUST
    ========================================================== */

    .product-trust-list {
        display: grid;
        gap: .65rem;
        margin-top: auto;
        padding-top: 1.5rem;
    }

    .product-trust-list > div {
        display: flex;
        align-items: center;
        gap: .65rem;
        color: var(--store-muted);
        font-size: .78rem;
    }

    .product-trust-list i {
        color: var(--store-primary-dark);
        font-size: 1rem;
    }


    /* =========================================================
       PRODUCT INFORMATION
    ========================================================== */

    .product-information-section {
        padding: 0 0 4rem;
        background: #fff;
    }

    .product-tabs-card {
        overflow: hidden;
        border: 1px solid var(--store-border);
        border-radius: var(--store-radius);
        background: #fff;
    }

    .product-tabs {
        padding: 0 1.25rem;
        border-bottom: 1px solid var(--store-border);
        background: var(--store-light);
    }

    .product-tabs .nav-link {
        position: relative;
        padding: 1rem 1.15rem;
        border: 0;
        border-radius: 0;
        color: var(--store-muted);
        background: transparent;
        font-size: .84rem;
        font-weight: 800;
    }

    .product-tabs .nav-link:hover {
        color: var(--store-dark);
    }

    .product-tabs .nav-link.active {
        color: var(--store-dark);
        background: transparent;
    }

    .product-tabs .nav-link.active::after {
        content: "";
        position: absolute;
        right: 1rem;
        bottom: -1px;
        left: 1rem;
        height: 2px;
        background: var(--store-primary);
    }

    .tab-count {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 20px;
        height: 20px;
        margin-left: .25rem;
        padding: 0 .3rem;
        border-radius: 10px;
        background: var(--store-primary);
        color: var(--store-dark);
        font-size: .65rem;
    }

    .product-tab-content {
        padding: 2rem;
    }

    .product-content-header {
        margin-bottom: 1.5rem;
    }

    .product-content-header h2 {
        margin: 0 0 .35rem;
        color: var(--store-dark);
        font-size: 1.15rem;
        font-weight: 900;
    }

    .product-content-header p {
        margin: 0;
        color: var(--store-muted);
        font-size: .82rem;
    }


    /* =========================================================
       DESCRIPTION
    ========================================================== */

    .product-description {
        color: #555;
        font-size: .9rem;
        line-height: 1.85;
    }

    .product-meta-list {
        margin-top: 1.75rem;
        border-top: 1px solid var(--store-border);
    }

    .product-meta-item {
        display: flex;
        justify-content: space-between;
        gap: 1rem;
        padding: .8rem 0;
        border-bottom: 1px solid var(--store-border);
        font-size: .82rem;
    }

    .product-meta-item span {
        color: var(--store-muted);
    }

    .product-meta-item strong {
        color: var(--store-dark);
        text-align: right;
    }


    /* =========================================================
       SPECIFICATIONS
    ========================================================== */

    .product-specifications {
        overflow: hidden;
        border: 1px solid var(--store-border);
        border-radius: var(--store-radius);
    }

    .specification-group {
        border-bottom: 1px solid var(--store-border);
    }

    .specification-group:last-child {
        border-bottom: 0;
    }

    .specification-group-header {
        display: flex;
        align-items: center;
        gap: .6rem;
        padding: .9rem 1rem;
        border-bottom: 1px solid var(--store-border);
        background: var(--store-light);
    }

    .specification-group-header i {
        color: var(--store-primary-dark);
    }

    .specification-group-header h3 {
        margin: 0;
        color: var(--store-dark);
        font-size: .88rem;
        font-weight: 800;
    }

    .specification-row {
        display: grid;
        grid-template-columns: minmax(180px, 30%) 1fr;
        border-bottom: 1px solid var(--store-border);
    }

    .specification-row:last-child {
        border-bottom: 0;
    }

    .specification-name {
        padding: .85rem 1rem;
        background: #fafafa;
        color: var(--store-muted);
        font-size: .82rem;
        font-weight: 600;
    }

    .specification-value {
        padding: .85rem 1rem;
        color: var(--store-dark);
        font-size: .84rem;
        line-height: 1.6;
    }

    .specification-row:hover .specification-name {
        background: #fff9e6;
        color: var(--store-dark);
    }

    .specification-row:hover .specification-value {
        background: #fffdf5;
    }


    /* =========================================================
       REVIEW SUMMARY
    ========================================================== */

    .review-summary {
        display: flex;
        align-items: center;
        gap: 2rem;
        margin-bottom: 1.5rem;
        padding: 1.25rem;
        border: 1px solid var(--store-border);
        border-radius: var(--store-radius);
        background: var(--store-light);
    }

    .review-summary-score {
        min-width: 120px;
        text-align: center;
    }

    .review-summary-score strong {
        display: block;
        color: var(--store-dark);
        font-size: 2rem;
        font-weight: 900;
        line-height: 1;
    }

    .review-summary-score .rating-stars {
        margin: .45rem 0;
    }

    .review-summary-score span {
        color: var(--store-muted);
        font-size: .75rem;
    }

    .review-summary-description {
        padding-left: 2rem;
        border-left: 1px solid var(--store-border);
    }

    .review-summary-description strong {
        color: var(--store-dark);
        font-size: .85rem;
    }

    .review-summary-description p {
        margin: .3rem 0 0;
        color: var(--store-muted);
        font-size: .8rem;
    }


    /* =========================================================
       REVIEWS
    ========================================================== */

    .reviews-layout {
        display: grid;
        grid-template-columns: minmax(0, 1fr) 340px;
        gap: 1.5rem;
        align-items: start;
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

    .review-item-header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 1rem;
    }

    .review-author {
        color: var(--store-dark);
        font-size: .88rem;
    }

    .review-verified {
        display: inline-flex;
        align-items: center;
        color: #198754;
        font-size: .7rem;
        font-weight: 700;
        white-space: nowrap;
    }

    .review-title {
        margin: .75rem 0 .35rem;
        color: var(--store-dark);
        font-size: .92rem;
        font-weight: 800;
    }

    .review-comment {
        margin: .4rem 0;
        color: #555;
        font-size: .84rem;
        line-height: 1.7;
    }

    .review-date {
        display: block;
        margin-top: .65rem;
        color: var(--store-muted);
        font-size: .7rem;
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
        margin: .9rem 0 .35rem;
        color: var(--store-dark);
        font-size: 1rem;
    }

    .review-empty p {
        margin: 0;
        font-size: .8rem;
    }


    /* =========================================================
       REVIEW FORM
    ========================================================== */

    .review-form-card,
    .review-info-card {
        padding: 1.25rem;
        border: 1px solid var(--store-border);
        border-radius: var(--store-radius);
        background: #fff;
    }

    .review-form-heading {
        display: flex;
        justify-content: space-between;
        gap: 1rem;
        margin-bottom: 1.25rem;
    }

    .review-form-heading h3 {
        margin: 0;
        color: var(--store-dark);
        font-size: .95rem;
        font-weight: 800;
    }

    .review-form-heading p {
        margin: .3rem 0 0;
        color: var(--store-muted);
        font-size: .76rem;
        line-height: 1.5;
    }

    .review-form-heading > i {
        color: var(--store-primary-dark);
        font-size: 1.35rem;
    }

    .review-form-field {
        margin-bottom: 1rem;
    }

    .review-form-field label {
        display: block;
        margin-bottom: .4rem;
        color: var(--store-dark);
        font-size: .78rem;
        font-weight: 700;
    }

    .review-form-field .form-control,
    .review-form-field .form-select {
        font-size: .82rem;
    }

    .review-delete-button {
        width: 100%;
        min-height: 38px;
        border: 1px solid #dc3545;
        border-radius: 4px;
        background: #fff;
        color: #dc3545;
        font-size: .76rem;
        font-weight: 700;
    }

    .review-delete-button:hover {
        background: #dc3545;
        color: #fff;
    }

    .review-info-card {
        display: flex;
        gap: .9rem;
    }

    .review-info-card > i {
        flex: 0 0 auto;
        color: var(--store-primary-dark);
        font-size: 1.35rem;
    }

    .review-info-card strong {
        color: var(--store-dark);
        font-size: .84rem;
    }

    .review-info-card p {
        margin: .4rem 0 1rem;
        color: var(--store-muted);
        font-size: .76rem;
        line-height: 1.6;
    }


    /* =========================================================
       EMPTY CONTENT
    ========================================================== */

    .product-empty-content {
        padding: 2.5rem 1rem;
        color: var(--store-muted);
        text-align: center;
    }

    .product-empty-content i {
        font-size: 2rem;
    }

    .product-empty-content p {
        margin: .75rem 0 0;
        font-size: .82rem;
    }


    /* =========================================================
       RELATED PRODUCTS
    ========================================================== */

    .related-products-section {
        padding-top: 1rem;
    }


    /* =========================================================
       RESPONSIVE
    ========================================================== */

    @media (max-width: 991.98px) {

        .product-gallery-image,
        .product-gallery-placeholder {
            height: 450px;
        }

        .product-trust-list {
            margin-top: 1.5rem;
        }

        .reviews-layout {
            grid-template-columns: 1fr;
        }

    }

    @media (max-width: 767.98px) {

        .product-detail-section {
            padding: 2rem 0 3rem;
        }

        .product-gallery-image,
        .product-gallery-placeholder {
            height: 340px;
        }

        .product-detail-title {
            font-size: 1.75rem;
        }

        .product-detail-price {
            font-size: 1.65rem;
        }

        .purchase-row {
            align-items: stretch;
        }

        .quantity-control {
            width: 100px;
            flex-basis: 100px;
        }

        .product-tabs {
            padding: 0 .5rem;
        }

        .product-tabs .nav-link {
            padding: .85rem .7rem;
            font-size: .76rem;
        }

        .product-tabs .nav-link.active::after {
            right: .6rem;
            left: .6rem;
        }

        .product-tab-content {
            padding: 1.25rem;
        }

        .specification-row {
            grid-template-columns: 1fr;
        }

        .specification-name {
            padding-bottom: .45rem;
            background: #fafafa;
        }

        .specification-value {
            padding-top: .45rem;
        }

        .review-summary {
            flex-direction: column;
            align-items: stretch;
            gap: 1rem;
        }

        .review-summary-score {
            text-align: left;
        }

        .review-summary-description {
            padding-top: 1rem;
            padding-left: 0;
            border-top: 1px solid var(--store-border);
            border-left: 0;
        }

    }

    @media (max-width: 575.98px) {

        .product-login-box {
            align-items: flex-start;
        }

        .product-secondary-actions {
            flex-direction: column;
        }

        .product-secondary-actions form {
            width: 100%;
        }

        .purchase-row {
            flex-direction: column;
        }

        .quantity-control {
            width: 100%;
        }

        .product-gallery-image,
        .product-gallery-placeholder {
            height: 300px;
        }

        .review-item-header {
            flex-direction: column;
        }

        .review-verified {
            margin-top: -.35rem;
        }

    }

</style>
@endpush

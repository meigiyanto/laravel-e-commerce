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

@extends('layouts.storefront')

@section('title', 'Shop - MeiStore')

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
<section class="store-section pb-0">

    <div class="container">

        <div class="store-section-header">

            <div>
                <h1 class="store-section-title">
                    Semua Produk
                </h1>

                <p class="store-section-subtitle">
                    Temukan berbagai produk terbaik di MeiStore.
                </p>
            </div>

            <div class="text-muted small">
                <i class="bi bi-box-seam me-1"></i>

                {{ $products->total() }}
                produk
            </div>

        </div>

    </div>

</section>


{{-- =========================================================
     SEARCH & FILTER
========================================================= --}}
<section class="py-4">
    <div class="container">
        <div class="border rounded-3 bg-light p-3 p-lg-4">
            <form action="{{ route('storefront.shop') }}" method="GET">
                <div class="row g-3 align-items-end">
                    <div class="col-12 col-md-2">
                        <label for="view" class="form-label fw-bold small">View</label>
                        <div class="btn-group">
                            <button type="button" id="list" class="btn btn-secondary"><i class="bi bi-list"></i></button>
                            <button type="button" id="grid" class="btn btn-outline-secondary"><i class="bi bi-grid"></i></button>
                        </div>
                    </div>
                    {{-- Search --}}
                    <div class="col-12 col-md-4">
                        <label for="shop-search" class="form-label fw-bold small">
                            Cari Produk
                        </label>

                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0">
                                <i class="bi bi-search text-muted"></i>
                            </span>

                            <input
                                type="search"
                                id="shop-search"
                                name="q"
                                class="form-control border-start-0"
                                value="{{ request('q') }}"
                                placeholder="Cari berdasarkan nama atau deskripsi produk..."
                            >

                        </div>

                    </div>


                    {{-- Category --}}
                    <div class="col-12 col-md-4">
                        <label
                            for="shop-category"
                            class="form-label fw-bold small"
                        >
                            Kategori
                        </label>

                        <select
                            id="shop-category"
                            name="category"
                            class="form-select"
                        >

                            <option value="">
                                Semua Kategori
                            </option>

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
                    <div class="col-12 col-md-2">

                        <button
                            type="submit"
                            class="store-add-cart"
                        >
                            <i class="bi bi-search"></i>
                            Cari
                        </button>

                    </div>

                </div>

            </form>

        </div>

    </div>

</section>


{{-- =========================================================
     ACTIVE FILTERS
========================================================= --}}
@if (request('q') || request('category'))

    <section class="pb-3">

        <div class="container">

            <div class="d-flex flex-wrap align-items-center gap-2">

                <span class="small text-muted fw-semibold">
                    Filter aktif:
                </span>


                @if (request('q'))

                    <span class="badge rounded-pill text-bg-light border text-dark px-3 py-2">

                        <i class="bi bi-search me-1"></i>

                        {{ request('q') }}

                    </span>

                @endif


                @if (request('category'))

                    @php
                        $selectedCategory = $categories
                            ->firstWhere('slug', request('category'));
                    @endphp

                    @if ($selectedCategory)

                        <span class="badge rounded-pill text-bg-light border text-dark px-3 py-2">

                            <i class="bi bi-grid me-1"></i>

                            {{ $selectedCategory->name }}

                        </span>

                    @endif

                @endif


                <a
                    href="{{ route('storefront.shop') }}"
                    class="btn btn-sm btn-outline-secondary rounded-pill"
                >
                    <i class="bi bi-x-lg me-1"></i>
                    Reset
                </a>

            </div>

        </div>

    </section>

@endif


{{-- =========================================================
     PRODUCT RESULT HEADER
========================================================= --}}
<section class="pb-3">

    <div class="container">

        <div class="d-flex justify-content-between align-items-center">

            <div>

                @if ($products->total() > 0)

                    <span class="small text-muted">

                        Menampilkan

                        <strong class="text-dark">
                            {{ $products->firstItem() }}
                        </strong>

                        -

                        <strong class="text-dark">
                            {{ $products->lastItem() }}
                        </strong>

                        dari

                        <strong class="text-dark">
                            {{ $products->total() }}
                        </strong>

                        produk

                    </span>

                @else

                    <span class="small text-muted">
                        Tidak ada produk ditemukan.
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

        <div class="row g-3 g-md-4">

            @forelse ($products as $product)

                <div class="col-6 col-md-4 col-lg-3">

                    <article class="store-product-card">

                        {{-- Product image --}}
                        <div class="store-product-image-wrap">

                            <a
                                href="{{ route('storefront.product', $product->slug) }}"
                            >

                                @if ($product->image)

                                    <img
                                        src="{{ $product->image }}"
                                        alt="{{ $product->name }}"
                                        class="store-product-image"
                                        loading="lazy"
                                    >

                                @else

                                    <div class="store-product-placeholder">
                                        <i class="bi bi-image"></i>
                                    </div>

                                @endif

                            </a>


                            {{-- Product actions --}}
                            @auth

                                <div class="store-product-actions">

                                    {{-- Wishlist --}}
                                    <form
                                        action="{{ route('wishlist.store', $product) }}"
                                        method="POST"
                                        class="wishlist-form"
                                    >
                                        @csrf

                                        <button
                                            type="submit"
                                            class="store-product-action"
                                            title="Tambah ke Wishlist"
                                            aria-label="Tambah ke Wishlist"
                                        >
                                            <i class="bi bi-heart"></i>
                                        </button>

                                    </form>


                                    {{-- Compare --}}
                                    <form
                                        action="{{ route('compare.store', $product) }}"
                                        method="POST"
                                        class="compare-form"
                                    >
                                        @csrf

                                        <button
                                            type="submit"
                                            class="store-product-action"
                                            title="Bandingkan produk"
                                            aria-label="Bandingkan produk"
                                        >
                                            <i class="bi bi-bar-chart"></i>
                                        </button>

                                    </form>

                                </div>

                            @endauth

                        </div>


                        {{-- Product body --}}
                        <div class="store-product-body">

                            {{-- Category --}}
                            <div class="store-product-category">

                                {{ $product->category?->name ?? 'Tanpa kategori' }}

                                @if ($product->subCategory)
                                    <span class="text-muted">
                                        / {{ $product->subCategory->name }}
                                    </span>
                                @endif

                            </div>


                            {{-- Product name --}}
                            <a
                                href="{{ route('storefront.product', $product->slug) }}"
                            >
                                <h2 class="store-product-name">
                                    {{ $product->name }}
                                </h2>
                            </a>


                            {{-- Price --}}
                            <div class="store-product-price">

                                Rp
                                {{ number_format($product->price, 0, ',', '.') }}

                            </div>


                            {{-- Stock --}}
                            <div class="store-product-stock">

                                @if ($product->stock > 0)

                                    <i class="bi bi-check-circle-fill text-success me-1"></i>

                                    Tersedia
                                    ({{ $product->stock }})

                                @else

                                    <i class="bi bi-x-circle-fill text-danger me-1"></i>

                                    Stok habis

                                @endif

                            </div>


                            {{-- Add cart --}}
                            <div class="store-product-footer">

                                @if ($product->stock > 0)

                                    @auth

                                        <form
                                            action="{{ route('cart.store') }}"
                                            method="POST"
                                            class="add-to-cart-form"
                                        >

                                            @csrf

                                            <input
                                                type="hidden"
                                                name="product_id"
                                                value="{{ $product->id }}"
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
                                                Tambah ke Keranjang
                                            </button>

                                        </form>

                                    @else

                                        <a
                                            href="{{ route('login') }}"
                                            class="store-add-cart"
                                        >
                                            <i class="bi bi-person"></i>
                                            Login untuk Membeli
                                        </a>

                                    @endauth

                                @else

                                    <button
                                        type="button"
                                        class="store-add-cart"
                                        disabled
                                        style="opacity: .55; cursor: not-allowed;"
                                    >
                                        <i class="bi bi-x-circle"></i>
                                        Stok Habis
                                    </button>

                                @endif

                            </div>

                        </div>

                    </article>

                </div>

            @empty

                {{-- Empty state --}}
                <div class="col-12">

                    <div class="text-center py-5">

                        <div
                            class="mx-auto mb-4 d-flex align-items-center justify-content-center rounded-circle bg-light"
                            style="width: 90px; height: 90px;"
                        >
                            <i class="bi bi-search fs-1 text-muted"></i>
                        </div>

                        <h2 class="h4 fw-bold mb-2">
                            Produk Tidak Ditemukan
                        </h2>

                        <p class="text-muted mb-4">
                            Coba gunakan kata kunci lain atau pilih
                            kategori yang berbeda.
                        </p>

                        <a
                            href="{{ route('storefront.shop') }}"
                            class="store-btn-primary"
                        >
                            <i class="bi bi-grid"></i>
                            Lihat Semua Produk
                        </a>

                    </div>

                </div>

            @endforelse

        </div>


        {{-- =====================================================
             PAGINATION
        ====================================================== --}}
        @if ($products->hasPages())

            <div class="d-flex justify-content-center mt-5">

                {{ $products->onEachSide(1)->links('pagination::bootstrap-5') }}

            </div>

        @endif

    </div>

</section>

@endsection


@push('scripts')

<script>
document.addEventListener('DOMContentLoaded', function () {

    /*
    |--------------------------------------------------------------------------
    | Add To Cart
    |--------------------------------------------------------------------------
    */

    document
        .querySelectorAll('.add-to-cart-form')
        .forEach(function (form) {

            form.addEventListener('submit', async function (event) {

                event.preventDefault();

                const button =
                    form.querySelector('.add-to-cart-button');

                if (!button) {
                    return;
                }

                const originalHtml =
                    button.innerHTML;

                button.disabled = true;

                button.innerHTML = `
                    <span
                        class="spinner-border spinner-border-sm"
                        role="status"
                        aria-hidden="true"
                    ></span>

                    <span>Menambahkan...</span>
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
                                        .getAttribute('content'),
                            },

                            body: new FormData(form),
                        }
                    );


                    const data =
                        await response.json();


                    if (!response.ok || !data.success) {

                        throw new Error(
                            data.message ||
                            'Gagal menambahkan produk ke keranjang.'
                        );

                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Global cart badge from storefront layout
                    |--------------------------------------------------------------------------
                    */

                    if (
                        typeof window.updateCartBadge ===
                        'function'
                    ) {
                        window.updateCartBadge(
                            data.cart_count
                        );
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Global notification from storefront layout
                    |--------------------------------------------------------------------------
                    */

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

                    } else {

                        alert(
                            error.message ||
                            'Terjadi kesalahan.'
                        );

                    }

                } finally {

                    button.disabled = false;
                    button.innerHTML = originalHtml;

                }

            });

        });


    /*
    |--------------------------------------------------------------------------
    | Wishlist
    |--------------------------------------------------------------------------
    */

    document
        .querySelectorAll('.wishlist-form')
        .forEach(function (form) {

            form.addEventListener('submit', async function (event) {

                event.preventDefault();

                const button =
                    form.querySelector('button');

                if (!button) {
                    return;
                }

                button.disabled = true;

                const originalHtml =
                    button.innerHTML;

                button.innerHTML =
                    '<span class="spinner-border spinner-border-sm"></span>';


                try {

                    const response =
                        await fetch(
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
                                            .getAttribute('content'),
                                },

                                body: new FormData(form),
                            }
                        );


                    const data =
                        await response.json();


                    if (!response.ok) {
                        throw new Error(
                            data.message ||
                            'Gagal menambahkan wishlist.'
                        );
                    }


                    if (
                        typeof window.showStoreNotification ===
                        'function'
                    ) {

                        window.showStoreNotification(
                            data.message ||
                            'Produk ditambahkan ke wishlist.',
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


    /*
    |--------------------------------------------------------------------------
    | Compare
    |--------------------------------------------------------------------------
    */

    document
        .querySelectorAll('.compare-form')
        .forEach(function (form) {

            form.addEventListener('submit', async function (event) {

                event.preventDefault();

                const button =
                    form.querySelector('button');

                if (!button) {
                    return;
                }

                button.disabled = true;

                const originalHtml =
                    button.innerHTML;

                button.innerHTML =
                    '<span class="spinner-border spinner-border-sm"></span>';


                try {

                    const response =
                        await fetch(
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
                                            .getAttribute('content'),
                                },

                                body: new FormData(form),
                            }
                        );


                    const data =
                        await response.json();


                    if (!response.ok) {
                        throw new Error(
                            data.message ||
                            'Gagal menambahkan produk ke compare.'
                        );
                    }


                    if (
                        typeof window.showStoreNotification ===
                        'function'
                    ) {

                        window.showStoreNotification(
                            data.message ||
                            'Produk ditambahkan ke compare.',
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

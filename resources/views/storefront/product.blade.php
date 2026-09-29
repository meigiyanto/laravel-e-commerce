@extends('layouts.storefront')

@section('title', $product->name . ' - MeiStore')

@section('content')

<section class="container py-4 py-lg-5">

    {{-- Breadcrumb --}}
    <nav aria-label="breadcrumb" class="mb-4">

        <ol class="breadcrumb">

            <li class="breadcrumb-item">
                <a href="{{ route('storefront.home') }}">
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

                    <a
                        href="{{ route('storefront.category', $product->category->slug) }}"
                    >
                        {{ $product->category->name }}
                    </a>

                </li>

            @endif

            <li
                class="breadcrumb-item active"
                aria-current="page"
            >
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

                    <img
                        src="{{ $product->image }}"
                        alt="{{ $product->name }}"
                        class="product-detail-image"
                    >

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


            <div class="product-detail-price mb-3">

                Rp {{ number_format($product->price, 0, ',', '.') }}

            </div>


            {{-- Stock --}}
            @if ($product->stock > 0)

                <div class="alert alert-success d-inline-flex align-items-center py-2 px-3">

                    <i class="bi bi-check-circle me-2"></i>

                    Stok tersedia:
                    <strong class="ms-1">
                        {{ $product->stock }}
                    </strong>

                </div>

            @else

                <div class="alert alert-danger d-inline-flex align-items-center py-2 px-3">

                    <i class="bi bi-x-circle me-2"></i>

                    Produk sedang habis.

                </div>

            @endif


            {{-- Description --}}
            <div class="mt-4">

                <h2 class="h5 fw-bold">
                    Deskripsi Produk
                </h2>

                <div class="text-muted product-description">

                    @if ($product->description)

                        {!! nl2br(e($product->description)) !!}

                    @else

                        Belum ada deskripsi produk.

                    @endif

                </div>

            </div>


            {{-- Add To Cart --}}
            @if ($product->stock > 0)

                @auth

                    <form
                        action="{{ route('cart.store') }}"
                        method="POST"
                        class="mt-4"
                    >

                        @csrf

                        <input
                            type="hidden"
                            name="product_id"
                            value="{{ $product->id }}"
                        >

                        <div class="row g-2">

                            <div class="col-4 col-sm-3">

                                <label
                                    for="quantity"
                                    class="form-label fw-semibold"
                                >
                                    Jumlah
                                </label>

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

                                <label class="form-label d-block">
                                    &nbsp;
                                </label>

                                <button
                                    type="submit"
                                    class="btn btn-primary w-100"
                                >
                                    <i class="bi bi-cart-plus me-2"></i>
                                    Tambah ke Keranjang
                                </button>

                            </div>

                        </div>

                    </form>

                @else

                    <div class="alert alert-light border mt-4">

                        <i class="bi bi-info-circle me-2"></i>

                        Silakan login terlebih dahulu untuk
                        menambahkan produk ke keranjang.

                        <div class="mt-3">

                            <a
                                href="{{ route('login') }}"
                                class="btn btn-primary"
                            >
                                Login
                            </a>

                            <a
                                href="{{ route('register') }}"
                                class="btn btn-outline-primary ms-2"
                            >
                                Daftar
                            </a>

                        </div>

                    </div>

                @endauth

            @else

                <button
                    type="button"
                    class="btn btn-secondary w-100 mt-4"
                    disabled
                >
                    Stok Habis
                </button>

            @endif

        </div>

    </div>


    {{-- Related Products --}}
    @if ($relatedProducts->isNotEmpty())

        <div class="mt-5 pt-5 border-top">

            <div class="d-flex justify-content-between align-items-center mb-4">

                <div>

                    <h2 class="section-title mb-1">
                        Produk Terkait
                    </h2>

                    <p class="text-muted mb-0">
                        Produk lain dari kategori yang sama.
                    </p>

                </div>

                @if ($product->category)

                    <a
                        href="{{ route('storefront.category', $product->category->slug) }}"
                        class="section-link"
                    >
                        Lihat Semua
                        <i class="bi bi-arrow-right"></i>
                    </a>

                @endif

            </div>


            <div class="row g-4">

                @foreach ($relatedProducts as $relatedProduct)

                    <div class="col-6 col-md-4 col-lg-3">

                        <div class="product-card">

                            <a
                                href="{{ route('storefront.product', $relatedProduct->slug) }}"
                            >

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

                                <a
                                    href="{{ route('storefront.product', $relatedProduct->slug) }}"
                                >

                                    <h3 class="product-name">
                                        {{ $relatedProduct->name }}
                                    </h3>

                                </a>

                                <div class="product-price">

                                    Rp
                                    {{ number_format($relatedProduct->price, 0, ',', '.') }}

                                </div>

                                <a
                                    href="{{ route('storefront.product', $relatedProduct->slug) }}"
                                    class="btn btn-outline-primary w-100 mt-3"
                                >
                                    Lihat Produk
                                </a>

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

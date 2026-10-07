@extends('layouts.storefront')

@section('title', 'Keranjang Belanja')

@section('content')
<div class="store-page">
    <div class="container py-4 py-lg-5">

        {{-- Breadcrumb --}}
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb store-breadcrumb">
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

                <li class="breadcrumb-item active" aria-current="page">
                    Keranjang
                </li>
            </ol>
        </nav>

        {{-- Flash messages --}}
        @if (session('success'))
            <div class="alert alert-success mb-4" role="alert">
                <i class="bi bi-check-circle me-2"></i>
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger mb-4" role="alert">
                <i class="bi bi-exclamation-circle me-2"></i>
                {{ session('error') }}
            </div>
        @endif

        {{-- Page heading --}}
        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
            <div>
                <span class="store-section-eyebrow">
                    Shopping Cart
                </span>

                <h1 class="store-page-title mb-1">
                    Keranjang Belanja
                </h1>

                <p class="text-muted mb-0">
                    Periksa produk dan jumlah pesanan Anda sebelum melanjutkan.
                </p>
            </div>

            <a
                href="{{ route('storefront.shop') }}"
                class="btn btn-outline-primary"
            >
                <i class="bi bi-arrow-left me-2"></i>
                Lanjut Belanja
            </a>
        </div>

        @if ($cart->items->isEmpty())

            {{-- Empty cart --}}
            <div class="store-empty-state text-center py-5">
                <div class="store-empty-icon mb-3">
                    <i class="bi bi-cart3"></i>
                </div>

                <h2 class="h4 mb-2">
                    Keranjang Anda masih kosong
                </h2>

                <p class="text-muted mb-4">
                    Belum ada produk yang ditambahkan ke keranjang.
                </p>

                <a
                    href="{{ route('storefront.shop') }}"
                    class="btn btn-primary"
                >
                    <i class="bi bi-bag me-2"></i>
                    Mulai Belanja
                </a>
            </div>

        @else

            <div class="row g-4">

                {{-- Cart items --}}
                <div class="col-lg-8">

                    <div class="store-card">

                        <div class="store-card-header">
                            <div>
                                <h2 class="h5 mb-1">
                                    Produk dalam Keranjang
                                </h2>

                                <span class="text-muted small">
                                    {{ $cart->items->sum('quantity') }} item
                                </span>
                            </div>
                        </div>

                        <div class="store-cart-items">

                            @foreach ($cart->items as $item)

                                @php
                                    $product = $item->product;
                                    $quantity = $item->quantity;
                                    $subtotal = $product->price * $quantity;

                                    $image = $product->image
                                        ?? $product->thumbnail
                                        ?? null;
                                @endphp

                                <div
                                    class="store-cart-item"
                                    data-product-id="{{ $product->id }}"
                                >

                                    {{-- Product image --}}
                                    <div class="store-cart-item-image">

                                        @if ($image)
                                            <img
                                                src="{{ asset('storage/' . $image) }}"
                                                alt="{{ $product->name }}"
                                                loading="lazy"
                                            >
                                        @else
                                            <div class="store-product-placeholder">
                                                <i class="bi bi-image"></i>
                                            </div>
                                        @endif

                                    </div>

                                    {{-- Product information --}}
                                    <div class="store-cart-item-content">

                                        <div class="d-flex flex-column flex-md-row justify-content-between gap-2">

                                            <div>
                                                <a
                                                    href="{{ route('storefront.product', $product->slug) }}"
                                                    class="store-cart-product-name"
                                                >
                                                    {{ $product->name }}
                                                </a>

                                                @if ($product->category)
                                                    <div class="small text-muted mt-1">
                                                        {{ $product->category->name }}
                                                    </div>
                                                @endif
                                            </div>

                                            <form
                                                action="{{ route('cart.destroy', $product) }}"
                                                method="POST"
                                                class="store-cart-remove-form"
                                            >
                                                @csrf
                                                @method('DELETE')

                                                <button
                                                    type="submit"
                                                    class="btn btn-link text-danger p-0"
                                                    title="Hapus produk"
                                                >
                                                    <i class="bi bi-trash3"></i>
                                                    <span class="d-none d-sm-inline">
                                                        Hapus
                                                    </span>
                                                </button>
                                            </form>

                                        </div>

                                        <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3 mt-3">

                                            {{-- Price --}}
                                            <div>
                                                <div class="small text-muted">
                                                    Harga
                                                </div>

                                                <strong>
                                                    Rp {{ number_format($product->price, 0, ',', '.') }}
                                                </strong>
                                            </div>

                                            {{-- Quantity --}}
                                            <form
                                                action="{{ route('cart.update', $product) }}"
                                                method="POST"
                                                class="quantity-form"
                                                data-product-id="{{ $product->id }}"
                                            >
                                                @csrf
                                                @method('PATCH')

                                                <label
                                                    for="quantity-{{ $product->id }}"
                                                    class="visually-hidden"
                                                >
                                                    Jumlah {{ $product->name }}
                                                </label>

                                                <div class="input-group input-group-sm store-quantity-control">

                                                    <button
                                                        type="button"
                                                        class="btn btn-outline-secondary quantity-decrease"
                                                        aria-label="Kurangi jumlah"
                                                    >
                                                        <i class="bi bi-dash"></i>
                                                    </button>

                                                    <input
                                                        type="number"
                                                        id="quantity-{{ $product->id }}"
                                                        name="quantity"
                                                        value="{{ $quantity }}"
                                                        min="1"
                                                        max="{{ $product->stock }}"
                                                        class="form-control text-center quantity-input"
                                                    >

                                                    <button
                                                        type="button"
                                                        class="btn btn-outline-secondary quantity-increase"
                                                        aria-label="Tambah jumlah"
                                                    >
                                                        <i class="bi bi-plus"></i>
                                                    </button>

                                                </div>
                                            </form>

                                            {{-- Subtotal --}}
                                            <div class="text-sm-end">
                                                <div class="small text-muted">
                                                    Subtotal
                                                </div>

                                                <strong class="cart-item-subtotal">
                                                    Rp {{ number_format($subtotal, 0, ',', '.') }}
                                                </strong>
                                            </div>

                                        </div>

                                    </div>
                                </div>

                            @endforeach

                        </div>
                    </div>
                </div>

                {{-- Summary --}}
                <div class="col-lg-4">

                    <div class="store-card store-cart-summary">

                        <div class="store-card-header">
                            <h2 class="h5 mb-0">
                                Ringkasan Pesanan
                            </h2>
                        </div>

                        <div class="store-card-body">

                            <div class="d-flex justify-content-between mb-3">
                                <span class="text-muted">
                                    Jumlah item
                                </span>

                                <strong class="cart-item-count">
                                    {{ $cart->items->sum('quantity') }}
                                </strong>
                            </div>

                            <div class="d-flex justify-content-between mb-3">
                                <span class="text-muted">
                                    Subtotal
                                </span>

                                <strong class="cart-total">
                                    Rp {{ number_format($total, 0, ',', '.') }}
                                </strong>
                            </div>

                            <div class="d-flex justify-content-between mb-3">
                                <span class="text-muted">
                                    Pengiriman
                                </span>

                                <span>
                                    Dihitung saat checkout
                                </span>
                            </div>

                            <hr>

                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <span class="fw-semibold">
                                    Total
                                </span>

                                <strong class="fs-5 cart-grand-total">
                                    Rp {{ number_format($total, 0, ',', '.') }}
                                </strong>
                            </div>

                            @auth

                                <a
                                    href="{{ route('checkout.index') }}"
                                    class="btn btn-primary w-100"
                                >
                                    Lanjut ke Checkout
                                    <i class="bi bi-arrow-right ms-2"></i>
                                </a>

                            @else

                                <a
                                    href="{{ route('login') }}"
                                    class="btn btn-primary w-100"
                                >
                                    Login untuk Checkout
                                    <i class="bi bi-box-arrow-in-right ms-2"></i>
                                </a>

                                <p class="small text-muted text-center mt-3 mb-0">
                                    Keranjang Anda akan tetap tersimpan saat Anda login.
                                </p>

                            @endauth

                        </div>
                    </div>

                </div>

            </div>

        @endif

    </div>
</div>
@endsection

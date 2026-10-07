@extends('layouts.storefront')

@section('title', 'Keranjang - MeiStore')

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
                <li class="breadcrumb-item">
                    Shop
                </li>
                <li class="breadcrumb-item active" aria-current="page">
                    Cart
                </li>
            </ol>
        </nav>
    </div>
</div>

<div class="container py-4 py-lg-5">
    {{-- Page Header --}}
    <div class="store-section-header mb-4">
        <div>
            <h1 class="store-section-title">Shopping Cart</h1>
            <p class="store-section-subtitle">Please double check the product before proceeding to checkout.Periksa kembali produk sebelum melanjutkan ke checkout.</p>
        </div>
    </div>

    {{-- Flash Messages --}}
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show shadow-sm mb-4">
            <i class="bi bi-check-circle me-2"></i>
            {{ session('success') }}

            <button
                type="button"
                class="btn-close"
                data-bs-dismiss="alert"
                aria-label="Close"
            ></button>
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show shadow-sm mb-4">
            <i class="bi bi-exclamation-circle me-2"></i>
            {{ session('error') }}

            <button
                type="button"
                class="btn-close"
                data-bs-dismiss="alert"
                aria-label="Close"
            ></button>
        </div>
    @endif


    {{-- Validation Errors --}}
    @if ($errors->any())
        <div class="alert alert-danger shadow-sm mb-4">
            <div class="fw-bold mb-2">
                <i class="bi bi-exclamation-triangle me-2"></i>
                Terjadi kesalahan. Something went wrong!
            </div>

            <ul class="mb-0 ps-4">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif


    @if ($cart->items->isEmpty())
        {{-- Empty Cart --}}
        <div class="border rounded-3 bg-white text-center py-5 px-3">

            <div
                class="d-inline-flex align-items-center justify-content-center rounded-circle mb-4"
                style="
                    width: 90px;
                    height: 90px;
                    background: #fff7d6;
                    color: var(--store-primary-dark);
                "
            >
                <i class="bi bi-cart-x fs-1"></i>
            </div>

            <h2 class="h4 fw-bold mb-2">
                Keranjang Anda masih kosong. Your cart is still empty
            </h2>

            <p class="text-muted mb-4"> No products have been added to your cart yet. Find the product you're looking for.
                Belum ada produk yang ditambahkan ke keranjang.
                Yuk, temukan produk yang Anda inginkan.
            </p>

            <a
                href="{{ route('storefront.shop') }}"
                class="store-btn-primary"
            >
                <i class="bi bi-shop"></i>
                Start Shopping
            </a>

        </div>

    @else

        <div class="row g-4 align-items-start">

            {{-- =====================================================
                 CART ITEMS
            ====================================================== --}}
            <div class="col-lg-8">

                {{-- Desktop table header --}}
                <div class="d-none d-md-grid mb-2 px-3 text-muted small fw-semibold"
                     style="grid-template-columns: 1fr 150px 130px; gap: 1rem;">
                    <span>Product</span>
                    <span class="text-center">Amount</span>
                    <span class="text-end">Sub Total</span>
                </div>


                @foreach ($cart->items as $item)

                    <article
                        class="store-cart-item border rounded-3 bg-white mb-3"
                        data-cart-item="{{ $item->id }}"
                    >

                        <div class="p-3 p-md-4">

                            <div class="row g-3 align-items-center">

                                {{-- Product Image --}}
                                <div class="col-4 col-sm-3 col-md-3">

                                    <a
                                        href="{{ route('storefront.product', $item->product->slug) }}"
                                        class="d-block"
                                    >

                                        @if ($item->product->image)

                                            <img
                                                src="{{ asset('storage/' . $item->product->image) }}"
                                                alt="{{ $item->product->name }}"
                                                class="store-cart-image"
                                            >

                                        @else

                                            <div class="store-cart-image store-cart-placeholder">
                                                <i class="bi bi-image"></i>
                                            </div>

                                        @endif

                                    </a>

                                </div>


                                {{-- Product Information --}}
                                <div class="col-8 col-sm-9 col-md-4">

                                    @if ($item->product->category)
                                        <div class="store-product-category mb-1">
                                            {{ $item->product->category->name }}
                                        </div>
                                    @endif

                                    <a
                                        href="{{ route('storefront.product', $item->product->slug) }}"
                                    >
                                        <h2 class="store-cart-product-name">
                                            {{ $item->product->name }}
                                        </h2>
                                    </a>

                                    <div class="store-product-price">
                                        Rp {{ number_format($item->product->price, 0, ',', '.') }}
                                    </div>

                                    <div class="store-product-stock mt-1">
                                        Stock available:
                                        {{ $item->product->stock }}
                                    </div>

                                    {{-- Mobile remove --}}
                                    <form
                                        action="{{ route('cart.destroy', $item) }}"
                                        method="POST"
                                        class="mt-3 d-md-none"
                                    >
                                        @csrf
                                        @method('DELETE')

                                        <button
                                            type="submit"
                                            class="btn btn-sm btn-outline-danger"
                                            onclick="return confirm('Hapus produk dari keranjang?')"
                                        >
                                            <i class="bi bi-trash me-1"></i>
                                            Remove
                                        </button>
                                    </form>

                                </div>


                                {{-- Quantity --}}
                                <div class="col-7 col-md-3">
                                    <label class="small fw-semibold text-muted d-block mb-2">Total</label>

                                    <form
                                        action="{{ route('cart.update', $item) }}"
                                        method="POST"
                                        class="quantity-form"
                                        data-item-id="{{ $item->id }}"
                                    >
                                        @csrf
                                        @method('PATCH')

                                        <div class="store-quantity-control">

                                            <button
                                                type="button"
                                                class="store-quantity-button quantity-minus"
                                                aria-label="Kurangi jumlah"
                                            >
                                                <i class="bi bi-dash"></i>
                                            </button>

                                            <input
                                                type="number"
                                                name="quantity"
                                                value="{{ $item->quantity }}"
                                                min="1"
                                                max="{{ $item->product->stock }}"
                                                class="form-control store-quantity-input quantity-input"
                                                aria-label="Jumlah {{ $item->product->name }}"
                                                required
                                            >

                                            <button
                                                type="button"
                                                class="store-quantity-button quantity-plus"
                                                aria-label="Add Total"
                                            >
                                                <i class="bi bi-plus"></i>
                                            </button>

                                        </div>
                                    </form>

                                </div>


                                {{-- Subtotal / Delete --}}
                                <div class="col-5 col-md-2 text-end">
                                    <div class="small text-muted mb-1">
                                        Subtotal
                                    </div>

                                    <div
                                        class="fw-bold cart-item-subtotal"
                                        data-item-id="{{ $item->id }}"
                                    >
                                        Rp
                                        {{ number_format(
                                            $item->product->price * $item->quantity,
                                            0,
                                            ',',
                                            '.'
                                        ) }}
                                    </div>

                                    <form
                                        action="{{ route('cart.destroy', $item) }}"
                                        method="POST"
                                        class="mt-3 d-none d-md-block"
                                    >
                                        @csrf
                                        @method('DELETE')

                                        <button
                                            type="submit"
                                            class="btn btn-sm btn-link text-danger p-0"
                                            onclick="return confirm('Hapus produk dari keranjang?')"
                                        >
                                            <i class="bi bi-trash me-1"></i>
                                            Delete
                                        </button>
                                    </form>

                                </div>
                            </div>
                        </div>
                    </article>
                @endforeach


                {{-- Continue Shopping --}}
                <div class="mt-3">

                    <a
                        href="{{ route('storefront.shop') }}"
                        class="btn btn-outline-secondary"
                    >
                        <i class="bi bi-arrow-left me-1"></i>
                        Continue Shopping
                    </a>

                </div>

            </div>


            {{-- =====================================================
                 ORDER SUMMARY
            ====================================================== --}}
            <div class="col-lg-4">

                <div class="store-cart-summary">

                    <div class="store-cart-summary-header">
                        <h2>Shopping Summary</h2>
                    </div>

                    <div class="p-4">

                        <div class="d-flex justify-content-between mb-3">
                            <span class="text-muted">
                                Total item
                            </span>

                            <strong id="cart-item-count">
                                {{ $cart->items->sum('quantity') }}
                            </strong>
                        </div>

                        <div class="d-flex justify-content-between mb-3">
                            <span class="text-muted">
                                Sub Total
                            </span>

                            <strong id="cart-total">
                                Rp {{ number_format($total, 0, ',', '.') }}
                            </strong>
                        </div>

                        <div class="d-flex justify-content-between mb-3">
                            <span class="text-muted">
                                Shipping
                            </span>

                            <span class="text-success fw-semibold">
                                Free
                            </span>
                        </div>

                        <hr>

                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <span class="fw-bold">
                                Total
                            </span>

                            <span class="store-summary-total">
                                Rp {{ number_format($total, 0, ',', '.') }}
                            </span>
                        </div>


                        <a
                            href="{{ route('checkout.index') }}"
                            class="store-checkout-button"
                        >
                            <span>
                                <i class="bi bi-lock me-1"></i>
                                Continue Checkout
                            </span>

                            <i class="bi bi-arrow-right"></i>
                        </a>


                        <div class="text-center mt-3">

                            <small class="text-muted">
                                <i class="bi bi-shield-check me-1"></i>
                                Secure and recorded payment system
                            </small>

                        </div>
                    </div>
                </div>


                {{-- Trust Card --}}
                <div class="border rounded-3 mt-3 p-3 bg-light">
                    <div class="d-flex gap-3">
                        <div class="text-warning fs-4">
                            <i class="bi bi-shield-check"></i>
                        </div>
                        <div>
                            <div class="fw-bold small">
                                Shop with peace of mind
                            </div>
                            <div class="text-muted small mt-1">
                                Your order and payment data are processed through the MeiStore system.
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    @endif
</div>

@endsection

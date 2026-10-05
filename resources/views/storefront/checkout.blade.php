@extends('layouts.storefront')

@section('title', 'Checkout - MeiStore')

@section('content')

<div class="container py-4 py-lg-5">

    {{-- Header --}}
    <div class="mb-4"> 
        <h1 class="store-section-title">
            Checkout
        </h1>

        <p class="store-section-subtitle">
            Lengkapi informasi pengiriman dan pilih metode pembayaran
            untuk menyelesaikan pesanan.
        </p>

    </div>


    {{-- Progress --}}
    <div class="store-checkout-progress mb-4">

        <div class="store-checkout-step completed">
            <span class="store-checkout-step-number">
                <i class="bi bi-check"></i>
            </span>

            <span>Keranjang</span>
        </div>

        <div class="store-checkout-line"></div>

        <div class="store-checkout-step active">
            <span class="store-checkout-step-number">
                2
            </span>

            <span>Checkout</span>
        </div>

        <div class="store-checkout-line"></div>

        <div class="store-checkout-step">
            <span class="store-checkout-step-number">
                3
            </span>

            <span>Pembayaran</span>
        </div>

    </div>


    {{-- Validation Errors --}}
    @if ($errors->any())

        <div class="alert alert-danger shadow-sm mb-4">

            <div class="fw-bold mb-2">
                <i class="bi bi-exclamation-triangle me-2"></i>
                Periksa kembali data Anda.
            </div>

            <ul class="mb-0 ps-4">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>

        </div>

    @endif


    <form
        action="{{ route('checkout.store') }}"
        method="POST"
    >
        @csrf

        <div class="row g-4 align-items-start">

            {{-- =================================================
                 LEFT COLUMN
            ================================================== --}}
            <div class="col-lg-7">


                {{-- Customer Information --}}
                <section class="store-checkout-card mb-4">

                    <div class="store-checkout-card-header">

                        <div class="store-checkout-card-icon">
                            <i class="bi bi-person"></i>
                        </div>

                        <div>
                            <h2>
                                Informasi Penerima
                            </h2>

                            <p>
                                Masukkan informasi penerima pesanan.
                            </p>
                        </div>

                    </div>


                    <div class="p-4">

                        {{-- Name --}}
                        <div class="mb-3">

                            <label
                                for="customer_name"
                                class="form-label fw-semibold"
                            >
                                Nama Penerima
                                <span class="text-danger">*</span>
                            </label>

                            <input
                                type="text"
                                id="customer_name"
                                name="customer_name"
                                value="{{ old('customer_name', auth()->user()->name) }}"
                                class="form-control store-form-control @error('customer_name') is-invalid @enderror"
                                placeholder="Masukkan nama penerima"
                                autocomplete="name"
                                required
                            >

                            @error('customer_name')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror

                        </div>


                        {{-- Phone --}}
                        <div class="mb-3">

                            <label
                                for="phone"
                                class="form-label fw-semibold"
                            >
                                Nomor Telepon
                                <span class="text-danger">*</span>
                            </label>

                            <input
                                type="tel"
                                id="phone"
                                name="phone"
                                value="{{ old('phone') }}"
                                class="form-control store-form-control @error('phone') is-invalid @enderror"
                                placeholder="08xxxxxxxxxx"
                                autocomplete="tel"
                                required
                            >

                            @error('phone')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror

                        </div>


                        {{-- Address --}}
                        <div class="mb-3">

                            <label
                                for="shipping_address"
                                class="form-label fw-semibold"
                            >
                                Alamat Pengiriman
                                <span class="text-danger">*</span>
                            </label>

                            <textarea
                                id="shipping_address"
                                name="shipping_address"
                                rows="5"
                                class="form-control store-form-control @error('shipping_address') is-invalid @enderror"
                                placeholder="Nama jalan, nomor rumah, RT/RW, desa, kecamatan, kabupaten, provinsi, dan kode pos"
                                autocomplete="street-address"
                                required
                            >{{ old('shipping_address') }}</textarea>

                            @error('shipping_address')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror

                            <div class="form-text">
                                Pastikan alamat lengkap agar pesanan dapat
                                dikirim dengan benar.
                            </div>

                        </div>


                        {{-- Notes --}}
                        <div>

                            <label
                                for="notes"
                                class="form-label fw-semibold"
                            >
                                Catatan
                                <span class="text-muted fw-normal">
                                    (opsional)
                                </span>
                            </label>

                            <textarea
                                id="notes"
                                name="notes"
                                rows="3"
                                class="form-control store-form-control @error('notes') is-invalid @enderror"
                                placeholder="Contoh: Tolong kirim pada sore hari."
                            >{{ old('notes') }}</textarea>

                            @error('notes')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror

                        </div>

                    </div>

                </section>


                {{-- Payment --}}
                <section class="store-checkout-card">

                    <div class="store-checkout-card-header">

                        <div class="store-checkout-card-icon">
                            <i class="bi bi-credit-card"></i>
                        </div>

                        <div>
                            <h2>
                                Metode Pembayaran
                            </h2>

                            <p>
                                Pilih metode pembayaran yang Anda inginkan.
                            </p>
                        </div>

                    </div>


                    <div class="p-4">

                        {{-- Stripe --}}
                        <label
                            class="store-payment-option"
                            for="payment_stripe"
                        >

                            <input
                                type="radio"
                                name="payment_method"
                                id="payment_stripe"
                                value="stripe"
                                class="form-check-input"
                                {{ old('payment_method') === 'stripe' ? 'checked' : '' }}
                                required
                            >

                            <span class="store-payment-icon">
                                <i class="bi bi-credit-card"></i>
                            </span>

                            <span class="store-payment-content">

                                <strong>
                                    Stripe
                                </strong>

                                <small>
                                    Bayar secara online menggunakan kartu
                                    atau metode pembayaran yang tersedia
                                    di Stripe.
                                </small>

                            </span>

                            <span class="store-payment-check">
                                <i class="bi bi-check"></i>
                            </span>

                        </label>


                        {{-- Midtrans --}}
                        <label
                            class="store-payment-option"
                            for="payment_midtrans"
                        >

                            <input
                                type="radio"
                                name="payment_method"
                                id="payment_midtrans"
                                value="midtrans"
                                class="form-check-input"
                                {{ old('payment_method') === 'midtrans' ? 'checked' : '' }}
                                required
                            >

                            <span class="store-payment-icon">
                                <i class="bi bi-wallet2"></i>
                            </span>

                            <span class="store-payment-content">

                                <strong>
                                    Midtrans
                                </strong>

                                <small>
                                    Gunakan berbagai metode pembayaran
                                    yang tersedia melalui Midtrans.
                                </small>

                            </span>

                            <span class="store-payment-check">
                                <i class="bi bi-check"></i>
                            </span>

                        </label>


                        {{-- COD --}}
                        <label
                            class="store-payment-option"
                            for="payment_cod"
                        >

                            <input
                                type="radio"
                                name="payment_method"
                                id="payment_cod"
                                value="cod"
                                class="form-check-input"
                                {{ old('payment_method') === 'cod' ? 'checked' : '' }}
                                required
                            >

                            <span class="store-payment-icon">
                                <i class="bi bi-cash-coin"></i>
                            </span>

                            <span class="store-payment-content">

                                <strong>
                                    Cash on Delivery
                                </strong>

                                <small>
                                    Bayar ketika pesanan diterima.
                                </small>

                            </span>

                            <span class="store-payment-check">
                                <i class="bi bi-check"></i>
                            </span>

                        </label>


                        @error('payment_method')
                            <div class="text-danger small mt-3">
                                {{ $message }}
                            </div>
                        @enderror


                        <div class="store-payment-security mt-4">

                            <i class="bi bi-shield-check"></i>

                            <span>
                                Informasi pembayaran Anda diproses
                                melalui sistem pembayaran yang dipilih.
                            </span>

                        </div>

                    </div>

                </section>

            </div>


            {{-- =================================================
                 RIGHT COLUMN
            ================================================== --}}
            <div class="col-lg-5">

                <aside class="store-checkout-summary">

                    <div class="store-checkout-summary-header">

                        <h2>
                            Ringkasan Pesanan
                        </h2>

                        <span>
                            {{ $cart->items->sum('quantity') }} item
                        </span>

                    </div>


                    <div class="p-4">

                        {{-- Products --}}
                        <div class="store-checkout-products mb-4">

                            @foreach ($cart->items as $item)

                                <div class="store-checkout-product">

                                    <div class="store-checkout-product-image">

                                        @if ($item->product->image)

                                            <img
                                                src="{{ asset('storage/' . $item->product->image) }}"
                                                alt="{{ $item->product->name }}"
                                            >

                                        @else

                                            <div class="store-checkout-placeholder">
                                                <i class="bi bi-image"></i>
                                            </div>

                                        @endif

                                        <span class="store-checkout-product-quantity">
                                            {{ $item->quantity }}
                                        </span>

                                    </div>


                                    <div class="store-checkout-product-info">

                                        <div class="store-checkout-product-name">
                                            {{ $item->product->name }}
                                        </div>

                                        <div class="small text-muted">
                                            Rp
                                            {{ number_format($item->product->price, 0, ',', '.') }}
                                            / item
                                        </div>

                                    </div>


                                    <div class="store-checkout-product-total">

                                        Rp
                                        {{ number_format(
                                            $item->product->price * $item->quantity,
                                            0,
                                            ',',
                                            '.'
                                        ) }}

                                    </div>

                                </div>

                            @endforeach

                        </div>


                        <hr>


                        {{-- Subtotal --}}
                        <div class="d-flex justify-content-between mb-3">

                            <span class="text-muted">
                                Subtotal
                            </span>

                            <span class="fw-semibold">
                                Rp {{ number_format($subtotal, 0, ',', '.') }}
                            </span>

                        </div>


                        {{-- Shipping --}}
                        <div class="d-flex justify-content-between mb-3">

                            <span class="text-muted">
                                Ongkos Kirim
                            </span>

                            @if ($shippingCost > 0)

                                <span class="fw-semibold">
                                    Rp {{ number_format($shippingCost, 0, ',', '.') }}
                                </span>

                            @else

                                <span class="text-success fw-semibold">
                                    Gratis
                                </span>

                            @endif

                        </div>


                        <hr>


                        {{-- Total --}}
                        <div class="d-flex justify-content-between align-items-end mb-4">

                            <div>

                                <div class="fw-bold">
                                    Total Pembayaran
                                </div>

                                <small class="text-muted">
                                    Termasuk ongkos kirim
                                </small>

                            </div>

                            <div class="store-checkout-total">
                                Rp {{ number_format($total, 0, ',', '.') }}
                            </div>

                        </div>


                        {{-- Submit --}}
                        <button
                            type="submit"
                            class="store-place-order-button"
                        >
                            <span>
                                <i class="bi bi-lock me-2"></i>
                                Buat Pesanan
                            </span>

                            <i class="bi bi-arrow-right"></i>
                        </button>


                        {{-- Back --}}
                        <a
                            href="{{ route('cart.index') }}"
                            class="store-back-cart-button"
                        >
                            <i class="bi bi-arrow-left"></i>
                            Kembali ke Keranjang
                        </a>


                        <div class="store-checkout-note">

                            <i class="bi bi-shield-check"></i>

                            <span>
                                Dengan membuat pesanan, Anda menyetujui
                                proses pemesanan dan pembayaran MeiStore.
                            </span>

                        </div>

                    </div>

                </aside>

            </div>

        </div>

    </form>
</div>
@endsection


@push('styles')
<style>

    /* =========================================================
       CHECKOUT PROGRESS
    ========================================================= */

    .store-checkout-progress {
        display: flex;
        align-items: center;

        max-width: 700px;
        margin-left: auto;
        margin-right: auto;

        padding: 1rem;

        border: 1px solid var(--store-border);
        border-radius: var(--store-radius);

        background: #fff;
    }

    .store-checkout-step {
        display: flex;
        align-items: center;
        gap: .5rem;

        color: var(--store-muted);

        font-size: .8rem;
        font-weight: 700;

        white-space: nowrap;
    }

    .store-checkout-step-number {
        width: 30px;
        height: 30px;

        display: inline-flex;
        align-items: center;
        justify-content: center;

        flex: 0 0 30px;

        border: 1px solid var(--store-border);
        border-radius: 50%;

        background: #fff;
        color: var(--store-muted);

        font-size: .78rem;
        font-weight: 800;
    }

    .store-checkout-step.active {
        color: var(--store-dark);
    }

    .store-checkout-step.active
    .store-checkout-step-number {
        border-color: var(--store-primary);
        background: var(--store-primary);
        color: var(--store-dark);
    }

    .store-checkout-step.completed {
        color: var(--store-dark);
    }

    .store-checkout-step.completed
    .store-checkout-step-number {
        border-color: var(--store-primary);
        background: var(--store-primary);
        color: var(--store-dark);
    }

    .store-checkout-line {
        flex: 1;

        height: 1px;

        margin: 0 1rem;

        background: var(--store-border);
    }


    /* =========================================================
       CHECKOUT CARD
    ========================================================= */

    .store-checkout-card {
        overflow: hidden;

        border: 1px solid var(--store-border);
        border-radius: var(--store-radius);

        background: #fff;
    }

    .store-checkout-card-header {
        display: flex;
        align-items: center;
        gap: .85rem;

        padding: 1.15rem 1.25rem;

        border-bottom: 1px solid var(--store-border);

        background: #fff;
    }

    .store-checkout-card-icon {
        width: 42px;
        height: 42px;

        display: inline-flex;
        align-items: center;
        justify-content: center;

        flex: 0 0 42px;

        border-radius: 50%;

        background: #fff7d6;
        color: var(--store-primary-dark);

        font-size: 1.15rem;
    }

    .store-checkout-card-header h2 {
        margin: 0;

        color: var(--store-dark);

        font-size: 1rem;
        font-weight: 800;
    }

    .store-checkout-card-header p {
        margin: .2rem 0 0;

        color: var(--store-muted);

        font-size: .76rem;
    }


    /* =========================================================
       FORM
    ========================================================= */

    .store-form-control {
        min-height: 46px;

        border-color: var(--store-border);

        border-radius: 5px;

        box-shadow: none !important;
    }

    textarea.store-form-control {
        min-height: auto;
    }

    .store-form-control:focus {
        border-color: var(--store-primary);

        box-shadow:
            0 0 0 .2rem rgba(252, 184, 0, .15) !important;
    }


    /* =========================================================
       PAYMENT OPTIONS
    ========================================================= */

    .store-payment-option {
        position: relative;

        display: flex;
        align-items: flex-start;
        gap: .85rem;

        padding: 1rem;

        margin-bottom: .75rem;

        border: 1px solid var(--store-border);
        border-radius: 7px;

        background: #fff;

        cursor: pointer;

        transition:
            border-color .2s ease,
            background-color .2s ease,
            box-shadow .2s ease;
    }

    .store-payment-option:last-of-type {
        margin-bottom: 0;
    }

    .store-payment-option:hover {
        border-color: #cfd3d7;
        background: #fafafa;
    }

    .store-payment-option:has(
        input:checked
    ) {
        border-color: var(--store-primary-dark);

        background: #fffdf3;

        box-shadow:
            0 0 0 1px var(--store-primary);
    }

    .store-payment-option input {
        margin-top: .2rem;

        accent-color: var(--store-primary-dark);
    }

    .store-payment-icon {
        width: 40px;
        height: 40px;

        display: inline-flex;
        align-items: center;
        justify-content: center;

        flex: 0 0 40px;

        border-radius: 5px;

        background: var(--store-light);
        color: var(--store-dark);

        font-size: 1.15rem;
    }

    .store-payment-content {
        display: flex;
        flex-direction: column;

        padding-right: 1.5rem;
    }

    .store-payment-content strong {
        color: var(--store-dark);

        font-size: .9rem;
    }

    .store-payment-content small {
        margin-top: .2rem;

        color: var(--store-muted);

        font-size: .75rem;
        line-height: 1.5;
    }

    .store-payment-check {
        position: absolute;

        top: 50%;
        right: 1rem;

        width: 23px;
        height: 23px;

        display: inline-flex;
        align-items: center;
        justify-content: center;

        transform: translateY(-50%);

        border: 1px solid var(--store-border);
        border-radius: 50%;

        color: transparent;

        font-size: .75rem;
    }

    .store-payment-option:has(
        input:checked
    ) .store-payment-check {
        border-color: var(--store-primary);
        background: var(--store-primary);
        color: var(--store-dark);
    }

    .store-payment-security {
        display: flex;
        align-items: flex-start;
        gap: .6rem;

        padding: .8rem;

        border-radius: 5px;

        background: var(--store-light);

        color: var(--store-muted);

        font-size: .74rem;
        line-height: 1.5;
    }

    .store-payment-security i {
        color: var(--store-primary-dark);
        font-size: 1rem;
    }


    /* =========================================================
       ORDER SUMMARY
    ========================================================= */

    .store-checkout-summary {
        position: sticky;
        top: 105px;

        overflow: hidden;

        border: 1px solid var(--store-border);
        border-radius: var(--store-radius);

        background: #fff;

        box-shadow: var(--store-shadow);
    }

    .store-checkout-summary-header {
        display: flex;
        align-items: center;
        justify-content: space-between;

        padding: 1rem 1.25rem;

        background: var(--store-dark);
        color: #fff;
    }

    .store-checkout-summary-header h2 {
        margin: 0;

        font-size: 1rem;
        font-weight: 800;
    }

    .store-checkout-summary-header span {
        color: rgba(255, 255, 255, .7);

        font-size: .76rem;
    }


    /* =========================================================
       PRODUCTS
    ========================================================= */

    .store-checkout-product {
        display: flex;
        align-items: center;
        gap: .75rem;

        margin-bottom: 1rem;
    }

    .store-checkout-product:last-child {
        margin-bottom: 0;
    }

    .store-checkout-product-image {
        position: relative;

        width: 65px;
        height: 65px;

        flex: 0 0 65px;

        overflow: visible;

        border-radius: 5px;

        background: var(--store-light);
    }

    .store-checkout-product-image img {
        width: 100%;
        height: 100%;

        display: block;

        object-fit: cover;

        border-radius: 5px;
    }

    .store-checkout-placeholder {
        width: 100%;
        height: 100%;

        display: flex;
        align-items: center;
        justify-content: center;

        border-radius: 5px;

        color: #adb5bd;
    }

    .store-checkout-product-quantity {
        position: absolute;

        top: -7px;
        right: -7px;

        min-width: 21px;
        height: 21px;

        display: inline-flex;
        align-items: center;
        justify-content: center;

        padding: 0 5px;

        border-radius: 50%;

        background: var(--store-primary);
        color: var(--store-dark);

        font-size: .68rem;
        font-weight: 800;
    }

    .store-checkout-product-info {
        min-width: 0;

        flex: 1;
    }

    .store-checkout-product-name {
        overflow: hidden;

        color: var(--store-dark);

        font-size: .82rem;
        font-weight: 700;
        line-height: 1.35;

        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
    }

    .store-checkout-product-total {
        flex: 0 0 auto;

        color: var(--store-dark);

        font-size: .82rem;
        font-weight: 800;
    }


    /* =========================================================
       TOTAL / ACTIONS
    ========================================================= */

    .store-checkout-total {
        color: var(--store-dark);

        font-size: 1.35rem;
        font-weight: 900;
    }

    .store-place-order-button {
        width: 100%;

        display: flex;
        align-items: center;
        justify-content: space-between;

        padding: .85rem 1rem;

        border: 1px solid var(--store-primary);
        border-radius: 5px;

        background: var(--store-primary);
        color: var(--store-dark);

        font-size: .92rem;
        font-weight: 800;

        cursor: pointer;
    }

    .store-place-order-button:hover {
        border-color: var(--store-primary-dark);
        background: var(--store-primary-dark);
    }

    .store-back-cart-button {
        width: 100%;

        display: flex;
        align-items: center;
        justify-content: center;
        gap: .4rem;

        margin-top: .65rem;

        padding: .7rem;

        border: 1px solid var(--store-border);
        border-radius: 5px;

        background: #fff;
        color: var(--store-dark);

        font-size: .84rem;
        font-weight: 700;
    }

    .store-back-cart-button:hover {
        background: var(--store-light);
        color: var(--store-dark);
    }

    .store-checkout-note {
        display: flex;
        align-items: flex-start;
        gap: .5rem;

        margin-top: 1rem;

        color: var(--store-muted);

        font-size: .72rem;
        line-height: 1.5;

        text-align: center;
    }

    .store-checkout-note i {
        color: var(--store-primary-dark);

        flex: 0 0 auto;
    }


    /* =========================================================
       MOBILE
    ========================================================= */

    @media (max-width: 767.98px) {

        .store-checkout-progress {
            padding: .75rem;
        }

        .store-checkout-step {
            font-size: .68rem;
        }

        .store-checkout-step-number {
            width: 27px;
            height: 27px;

            flex-basis: 27px;
        }

        .store-checkout-line {
            margin: 0 .5rem;
        }

        .store-checkout-card-header {
            padding: 1rem;
        }

        .store-checkout-card .p-4,
        .store-checkout-summary .p-4 {
            padding: 1rem !important;
        }

        .store-checkout-summary {
            position: static;
        }

        .store-checkout-product-total {
            font-size: .76rem;
        }

    }

</style>
@endpush

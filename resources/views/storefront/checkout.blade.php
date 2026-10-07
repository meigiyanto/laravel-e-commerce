@extends('layouts.storefront')

@section('title', config('app.name') . ' - Checkout')

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
                    Checkout
                </li>
            </ol>
        </nav>
    </div>
</div>

<div class="container py-4 py-lg-5">
    {{-- Header --}}
    <div class="mb-4">
        <h1 class="store-section-title">
            Checkout
        </h1>

        <p class="store-section-subtitle">Complete the shipping information and select a payment method to complete the order.</p>

    </div>


    {{-- Progress --}}
    <div class="store-checkout-progress mb-4">

        <div class="store-checkout-step completed">
            <span class="store-checkout-step-number">
                <i class="bi bi-check"></i>
            </span>

            <span>Cart</span>
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

            <span>Payment</span>
        </div>

    </div>


    {{-- Validation Errors --}}
    @if ($errors->any())

        <div class="alert alert-danger shadow-sm mb-4">

            <div class="fw-bold mb-2">
                <i class="bi bi-exclamation-triangle me-2"></i>
                Please check your data again.
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
                            <h2>Recipient's Information</h2>
                            <p>Enter the order recipient information.</p>
                        </div>
                    </div>


                    <div class="p-4">

                        {{-- Name --}}
                        <div class="mb-3">

                            <label
                                for="customer_name"
                                class="form-label fw-semibold"
                            >
                               Recipient's name
                                <span class="text-danger">*</span>
                            </label>

                            <input
                                type="text"
                                id="customer_name"
                                name="customer_name"
                                value="{{ old('customer_name', auth()->user()->name) }}"
                                class="form-control store-form-control @error('customer_name') is-invalid @enderror"
                                placeholder="Enter the recipient's name"
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
                                Phone Number
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
                                Shipping Address
                                <span class="text-danger">*</span>
                            </label>

                            <textarea
                                id="shipping_address"
                                name="shipping_address"
                                rows="5"
                                class="form-control store-form-control @error('shipping_address') is-invalid @enderror"
                                placeholder="Home or office address"
                                autocomplete="street-address"
                                required
                            >{{ old('shipping_address') }}</textarea>

                            @error('shipping_address')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror

                            <div class="form-text">Make sure the address is complete so that the order can be sent correctly.
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
                                    (optional)
                                </span>
                            </label>

                            <textarea
                                id="notes"
                                name="notes"
                                rows="3"
                                class="form-control store-form-control @error('notes') is-invalid @enderror"
                                placeholder="Example: Please send it in the afternoon."
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
                            <h2>Payment Method</h2>
                            <p> Select your desired payment method.</p>
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

                        <div class="store-payment-security mt-4">                            <i class="bi bi-shield-check"></i>
                            <span>Your payment information is processed through the selected system.</span>
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
                            Order Summary
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
                                Shipping Costs
                            </span>

                            @if ($shippingCost > 0)
                                <span class="fw-semibold">
                                    Rp {{ number_format($shippingCost, 0, ',', '.') }}
                                </span>
                            @else
                                <span class="text-success fw-semibold">
                                    Free
                                </span>
                            @endif
                        </div>


                        <hr>


                        {{-- Total --}}
                        <div class="d-flex justify-content-between align-items-end mb-4">
                            <div>
                                <div class="fw-bold">Total Payment</div>

                                <small class="text-muted">Including shipping costs</small>
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
                                Create Order
                            </span>

                            <i class="bi bi-arrow-right"></i>
                        </button>


                        {{-- Back --}}
                        <a
                            href="{{ route('cart.index') }}"
                            class="store-back-cart-button"
                        >
                            <i class="bi bi-arrow-left"></i>
                            Back to Cart
                        </a>


                        <div class="store-checkout-note">
                            <i class="bi bi-shield-check"></i>
                            <span>By placing an order, you agree to {{ config('app.name') }}'s ordering and payment process.</span>

                        </div>
                    </div>
                </aside>

            </div>
        </div>
    </form>
</div>
@endsection

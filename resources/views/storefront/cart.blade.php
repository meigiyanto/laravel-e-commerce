@extends('layouts.storefront')

@section('title', config('app.name') . ' - Shopping Cart')

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

                <li class="breadcrumb-item active" aria-current="page">
                    Cart
                </li>
            </ol>
        </nav>
    </div>
</div>


{{-- =========================================================
    CART
========================================================= --}}
<section class="store-section">
    <div class="container">

        {{-- Header --}}
        <div class="store-section-header">
            <div>
                <h1 class="store-section-title">Shopping Cart</h1>

                <p class="store-section-subtitle">Check the products and order quantity before proceeding.</p>
            </div>
        </div>


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


        @if ($cart->items->isEmpty())
            {{-- =================================================
                EMPTY CART
            ================================================== --}}
            <div class="store-empty-state text-center">
                <div class="store-empty-icon">
                    <i class="bi bi-cart3"></i>
                </div>

                <h2 class="h4 mb-2">Your cart still empty</h2>
                <p class="text-muted mb-4">No products have been added to the cart yet.</p>

                <a href="{{ route('storefront.shop') }}" class="store-btn-primary">
                    <i class="bi bi-bag me-2"></i>
                    Start Shopping
                </a>
            </div>
        @else
            {{-- =================================================
                CART CONTENT
            ================================================== --}}
            <div class="row g-4 align-items-start">
                {{-- =================================================
                    CART ITEMS
                ================================================== --}}
                <div class="col-lg-8">
                    <div class="card store-cart-card">
                        <div class="card-body">
                            {{-- Cart heading --}}
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div>
                                    <h2 class="h5 mb-1">Product on cart</h2>
                                    <span class="text-muted small">
                                        {{ $cart->items->sum('quantity') }} item
                                    </span>
                                </div>
                            </div>


                            {{-- Items --}}
                            <div class="store-cart-items">
                                @foreach ($cart->items as $item)
                                    @php
                                        $product = $item->product;
                                        $quantity = $item->quantity;
                                        $subtotal = $product->price * $quantity;
                                        $image = $product->image ?? 'placeholder.png';
                                        $imageUrl = null;
                                        if (filter_var($image, FILTER_VALIDATE_URL)) {
                                            $imageUrl = $image;
                                        } elseif ($image && Storage::disk('public')->exists($image)) {
                                            $imageUrl = asset('storage/' . $image);
                                        }

                                    @endphp

                                    <article class="store-cart-item" data-product-id="{{ $product->id }}">
                                        <div class="table table-responsive">
                                            <table class="table">
                                                <thead>
                                                    <tr>
                                                        <th></th>
                                                        <th></th>
                                                        <th></th>
                                                        <th></th>
                                                        <th></th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr>
                                                        <td>
                                                            {{-- Product image --}}
                                                            <div class="store-cart-item-image">
                                                                @if ($image)
                                                                    <a href="{{ route('storefront.product', $product->slug) }}" class="d-block">
                                                                        <img src="{{ $imageUrl }}" alt="{{ $product->name }}" class="store-cart-image" loading="lazy">
                                                                    </a>
                                                                    <!-- <p>{{ $imageUrl }}</p> -->
                                                                @else
                                                                    <a href="{{ route('storefront.product', $product->slug) }}" class="d-block" aria-label="{{ $product->name }}">
                                                                        <div class="store-cart-placeholder">
                                                                            <i class="bi bi-image"></i>
                                                                        </div>
                                                                    </a>
                                                                @endif
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <a href="{{ route('storefront.product', $product->slug) }}" class="store-cart-product-name">{{ $product->name }}</a>
                                                            @if ($product->category)
                                                                <div class="small text-muted mt-1">
                                                                    {{ $product->category->name }}
                                                                </div>
                                                            @endif
                                                        </td>
                                                        <td>
                                                            {{-- Price --}}                                                                                  <div class="small text-muted">Price</div>
                                                             <strong>Rp {{ number_format($product->price, 0, ',', '.') }}</strong>
                                                        </td>
                                                        <td>
                                                            {{-- Subtotal --}}
                                                            <div class="text-center">
                                                                <div class="small text-muted">Subtotal</div>
                                                                <strong class="cart-item-subtotal">Rp {{ number_format($subtotal, 0, ',', '.') }}</strong>
                                                            </div>
                                                            {{-- Quantity --}}
                                                            <form action="{{ route('cart.update', $product) }}" method="POST" class="quantity-form" data-product-id="{{ $product->id }}">
                                                                @csrf
                                                                @method('PATCH')

                                                                <label for="quantity-{{ $product->id }}" class="visually-hidden">Total {{ $product->name }}</label>

                                                                <div class="store-quantity-control">
                                                                    <button type="button" class="store-quantity-button quantity-decrease" aria-label="Decrease Quantity">
                                                                        <i class="bi bi-dash"></i>
                                                                    </button>

                                                                    <input
                                                                        type="number"
                                                                        id="quantity-{{ $product->id }}"
                                                                        name="quantity"
                                                                        value="{{ $quantity }}"
                                                                        min="1"
                                                                        max="{{ $product->stock }}"
                                                                        class="store-quantity-input quantity-input"
                                                                    >

                                                                    <button type="button" class="store-quantity-button quantity-increase" aria-label="Add Quantity">
                                                                        <i class="bi bi-plus"></i>
                                                                    </button>
                                                                </div>

                                                            </form>
                                                        </td>
                                                        <td>
                                                            {{-- Remove --}}
                                                            <form action="{{ route('cart.destroy', $product) }}" method="POST" class="store-cart-remove-form">

                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit" class="btn btn-link text-danger p-0" title="Delete Product">
                                                                    <i class="bi bi-trash3"></i>
                                                                </button>
                                                            </form>
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </article>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>


                {{-- =================================================
                    SUMMARY
                ================================================== --}}
                <div class="col-lg-4">
                    <aside class="card store-cart-summary">
                        <div class="store-cart-summary-header">
                            <h2>Order Summary</h2>
                        </div>


                        <div class="card-body">
                            <div class="d-flex justify-content-between mb-3">
                                <span class="text-muted">Total item</span>
                                <strong class="cart-item-count">{{ $cart->items->sum('quantity') }}</strong>
                            </div>

                            <div class="d-flex justify-content-between mb-3">
                                <span class="text-muted">Subtotal</span>
                                <strong class="cart-total">Rp {{ number_format($total, 0, ',', '.') }}</strong>
                            </div>


                            <div class="d-flex justify-content-between mb-3">
                                <span class="text-muted">Shipping</span>
                                <span class="small">Calculated at checkout</span>
                            </div>
                            <hr>
                            <div class="d-flex justify-content-between align-items-center mb-4">

                                <span class="fw-semibold">Total</span>
                                <strong class="fs-5 cart-grand-total">Rp {{ number_format($total, 0, ',', '.') }}</strong>
                            </div>

                            @auth
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <a href="{{ route('storefront.shop') }}" class="store-go-back-button">
                                            Continue Shopping
                                        </a>
                                    </div>
                                                                                                      <div>
                                        <a href="{{ route('checkout.index') }}" class="store-checkout-button">
                                            Continue Checkout
                                        </a>
                                    </div>
                                    <!--
                                    <div>
                                        <a href="{{ route('checkout.index') }}" class="store-checkout-button">
                                                                                                              <span>Continue to Checkout</span>
                                            <i class="bi bi-arrow-right me-2"></i>
                                        </a>
                                    </div>
                                    -->
                                </div>
                            @else
                                <a href="{{ route('login') }}" class="store-checkout-button">
                                    <span>Login to Checkout</span>
                                    <i class="bi bi-box-arrow-in-right"></i>
                                </a>

                                <p class="small text-muted text-center mt-3 mb-0">Your cart will be saved when you log in.</p>
                            @endauth

                        </div>
                    </aside>
                </div>
            </div>
        @endif
    </div>
</section>

@endsection

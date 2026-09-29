@extends('layouts.storefront')

@section('title', 'Keranjang - MeiStore')

@section('content')

<section class="container py-4 py-lg-5">
    <div class="mb-4">
        <h1 class="section-title mb-2">Keranjang Belanja</h1>
        <p class="text-muted mb-0">
            Periksa kembali produk yang ingin kamu beli.
        </p>
    </div>


    {{-- Flash Message --}}
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show">

            <i class="bi bi-check-circle me-2"></i>

            {{ session('success') }}

            <button
                type="button"
                class="btn-close"
                data-bs-dismiss="alert"
            ></button>

        </div>

    @endif


    @if (session('error'))

        <div class="alert alert-danger alert-dismissible fade show">

            <i class="bi bi-exclamation-circle me-2"></i>

            {{ session('error') }}

            <button
                type="button"
                class="btn-close"
                data-bs-dismiss="alert"
            ></button>

        </div>

    @endif


    @if ($cart->items->isEmpty())

        <div class="text-center py-5">

            <i class="bi bi-cart-x fs-1 text-muted"></i>

            <h2 class="h4 fw-bold mt-3">
                Keranjang masih kosong
            </h2>

            <p class="text-muted">
                Yuk cari produk yang ingin kamu beli.
            </p>

            <a
                href="{{ route('storefront.shop') }}"
                class="btn btn-primary"
            >
                <i class="bi bi-shop me-2"></i>
                Mulai Belanja
            </a>

        </div>

    @else

        <div class="row g-4">

            {{-- Cart Items --}}
            <div class="col-lg-8">

                @foreach ($cart->items as $item)

                    <div class="card border-0 shadow-sm rounded-4 mb-3">

                        <div class="card-body">

                            <div class="row g-3 align-items-center">

                                {{-- Image --}}
                                <div class="col-4 col-sm-3">

                                    <a
                                        href="{{ route('storefront.product', $item->product->slug) }}"
                                    >

                                        @if ($item->product->image)

                                            <img
                                                src="{{ $item->product->image }}"
                                                alt="{{ $item->product->name }}"
                                                class="cart-product-image"
                                            >

                                        @else

                                            <div class="cart-product-image d-flex align-items-center justify-content-center bg-light">

                                                <i class="bi bi-image fs-2 text-secondary"></i>

                                            </div>

                                        @endif

                                    </a>

                                </div>


                                {{-- Product --}}
                                <div class="col-8 col-sm-5">

                                    <div class="product-category">
                                        {{ $item->product->category?->name }}
                                    </div>

                                    <a
                                        href="{{ route('storefront.product', $item->product->slug) }}"
                                    >

                                        <h3 class="h6 fw-bold text-dark mb-2">

                                            {{ $item->product->name }}

                                        </h3>

                                    </a>
                                    <div class="product-price">

                                        Rp
                                        {{ number_format($item->product->price, 0, ',', '.') }}

                                    </div>

                                </div>


                                {{-- Quantity --}}
                                <div class="col-7 col-sm-2">

                                    <form
                                        action="{{ route('cart.update', $item) }}"
                                        method="POST"
                                    >

                                        @csrf
                                        @method('PATCH')

                                        <label class="form-label small fw-semibold">
                                            Jumlah
                                        </label>

                                        <input
                                            type="number"
                                            name="quantity"
                                            class="form-control"
                                            value="{{ $item->quantity }}"
                                            min="1"
                                            max="{{ $item->product->stock }}"
                                            onchange="this.form.submit()"
                                        >

                                    </form>

                                </div>


                                {{-- Delete --}}
                                <div class="col-5 col-sm-2 text-end">

                                    <div class="small text-muted mb-2">
                                        Subtotal
                                    </div>

                                    <div class="fw-bold mb-3">

                                        Rp
                                        {{ number_format($item->product->price * $item->quantity, 0, ',', '.') }}

                                    </div>

                                    <form
                                        action="{{ route('cart.destroy', $item) }}"
                                        method="POST"
                                    >

                                        @csrf
                                        @method('DELETE')

                                        <button
                                            type="submit"
                                            class="btn btn-sm btn-outline-danger"
                                            onclick="return confirm('Hapus produk dari keranjang?')"
                                        >

                                            <i class="bi bi-trash"></i>

                                        </button>

                                    </form>

                                </div>

                            </div>

                        </div>

                    </div>

                @endforeach

            </div>


            {{-- Summary --}}
            <div class="col-lg-4">

                <div class="card border-0 shadow-sm rounded-4">

                    <div class="card-body p-4">

                        <h2 class="h5 fw-bold mb-4">
                            Ringkasan Belanja
                        </h2>


                        <div class="d-flex justify-content-between mb-3">

                            <span class="text-muted">
                                Jumlah item
                            </span>

                            <strong>
                                {{ $cart->items->sum('quantity') }}
                            </strong>

                        </div>


                        <hr>


                        <div class="d-flex justify-content-between mb-4">

                            <span class="fw-bold">
                                Total
                            </span>

                            <span class="product-price">

                                Rp
                                {{ number_format($total, 0, ',', '.') }}

                            </span>

                        </div>

                        <a href="{{ route('checkout.index') }}" class="btn btn-primary w-100">
                            <i class="bi bi-credit-card me-1"></i>
                            Checkout
                        </a>
 
                        <div class="text-center mt-3">

                            <small class="text-muted">
                                Checkout akan tersedia pada tahap berikutnya.
                            </small>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    @endif

</section>

@endsection


@push('styles')

<style>
    .cart-product-image {
        width: 100%;
        height: 120px;
        object-fit: cover;
        border-radius: .75rem;
    }

    @media (max-width: 575.98px) {
        .cart-product-image {
            height: 100px;
        }

    }

</style>

@endpush

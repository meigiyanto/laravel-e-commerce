@extends('layouts.storefront')

@section('title', 'Keranjang - MeiStore')

@section('content')

<div class="container py-4 py-lg-5">

    {{-- Page Header --}}
    <div class="store-section-header mb-4">
        <div>
            <h1 class="store-section-title">Keranjang Belanja</h1>
            <p class="store-section-subtitle">
                Periksa kembali produk sebelum melanjutkan ke checkout.</p>
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
                Terjadi kesalahan
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
                Keranjang Anda masih kosong
            </h2>

            <p class="text-muted mb-4">
                Belum ada produk yang ditambahkan ke keranjang.
                Yuk, temukan produk yang Anda inginkan.
            </p>

            <a
                href="{{ route('storefront.shop') }}"
                class="store-btn-primary"
            >
                <i class="bi bi-shop"></i>
                Mulai Belanja
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
                    <span>Produk</span>
                    <span class="text-center">Jumlah</span>
                    <span class="text-end">Subtotal</span>
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
                                        Stok tersedia:
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
                                            Hapus
                                        </button>
                                    </form>

                                </div>


                                {{-- Quantity --}}
                                <div class="col-7 col-md-3">

                                    <label
                                        class="small fw-semibold text-muted d-block mb-2"
                                    >
                                        Jumlah
                                    </label>

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
                                                aria-label="Tambah jumlah"
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
                                            Hapus
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
                        Lanjut Belanja
                    </a>

                </div>

            </div>


            {{-- =====================================================
                 ORDER SUMMARY
            ====================================================== --}}
            <div class="col-lg-4">

                <div class="store-cart-summary">

                    <div class="store-cart-summary-header">
                        <h2>
                            Ringkasan Belanja
                        </h2>
                    </div>

                    <div class="p-4">

                        <div class="d-flex justify-content-between mb-3">
                            <span class="text-muted">
                                Jumlah item
                            </span>

                            <strong id="cart-item-count">
                                {{ $cart->items->sum('quantity') }}
                            </strong>
                        </div>

                        <div class="d-flex justify-content-between mb-3">
                            <span class="text-muted">
                                Subtotal
                            </span>

                            <strong id="cart-total">
                                Rp {{ number_format($total, 0, ',', '.') }}
                            </strong>
                        </div>

                        <div class="d-flex justify-content-between mb-3">
                            <span class="text-muted">
                                Pengiriman
                            </span>

                            <span class="text-success fw-semibold">
                                Gratis
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
                                Lanjut Checkout
                            </span>

                            <i class="bi bi-arrow-right"></i>
                        </a>


                        <div class="text-center mt-3">

                            <small class="text-muted">
                                <i class="bi bi-shield-check me-1"></i>
                                Pembayaran aman dan tercatat dalam sistem.
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
                                Belanja dengan tenang
                            </div>

                            <div class="text-muted small mt-1">
                                Data pesanan dan pembayaran Anda
                                diproses melalui sistem MeiStore.
                            </div>
                        </div>

                    </div>

                </div>

            </div>

        </div>

    @endif

</div>

@endsection


@push('styles')
<style>

    .store-cart-item {
        border-color: var(--store-border) !important;
        transition:
            border-color .2s ease,
            box-shadow .2s ease;
    }

    .store-cart-item:hover {
        border-color: #d5d8dc !important;
        box-shadow: var(--store-shadow);
    }

    .store-cart-image {
        width: 100%;
        height: 135px;

        display: block;

        object-fit: cover;

        border-radius: 6px;

        background: var(--store-light);
    }

    .store-cart-placeholder {
        display: flex;
        align-items: center;
        justify-content: center;

        color: #adb5bd;

        font-size: 2rem;
    }

    .store-cart-product-name {
        margin: 0 0 .5rem;

        color: var(--store-dark);

        font-size: .98rem;
        font-weight: 700;
        line-height: 1.45;
    }

    .store-cart-product-name:hover {
        color: var(--store-primary-dark);
    }

    .store-quantity-control {
        display: flex;

        width: 100%;
        max-width: 145px;

        border: 1px solid var(--store-border);
        border-radius: 5px;

        overflow: hidden;

        background: #fff;
    }

    .store-quantity-button {
        width: 38px;
        height: 38px;

        flex: 0 0 38px;

        display: inline-flex;
        align-items: center;
        justify-content: center;

        border: 0;

        background: #f8f9fa;
        color: var(--store-dark);

        cursor: pointer;
    }

    .store-quantity-button:hover:not(:disabled) {
        background: var(--store-primary);
    }

    .store-quantity-button:disabled {
        opacity: .45;
        cursor: not-allowed;
    }

    .store-quantity-input {
        min-width: 0;

        height: 38px;

        border: 0;
        border-left: 1px solid var(--store-border);
        border-right: 1px solid var(--store-border);

        border-radius: 0;

        text-align: center;
        box-shadow: none !important;
    }

    .store-quantity-input::-webkit-inner-spin-button,
    .store-quantity-input::-webkit-outer-spin-button {
        -webkit-appearance: none;
        margin: 0;
    }

    .store-quantity-input {
        -moz-appearance: textfield;
    }

    .store-cart-summary {
        position: sticky;
        top: 105px;

        overflow: hidden;

        border: 1px solid var(--store-border);
        border-radius: var(--store-radius);

        background: #fff;

        box-shadow: var(--store-shadow);
    }

    .store-cart-summary-header {
        padding: 1rem 1.25rem;

        border-bottom: 1px solid var(--store-border);

        background: var(--store-dark);
        color: #fff;
    }

    .store-cart-summary-header h2 {
        margin: 0;

        font-size: 1rem;
        font-weight: 800;
    }

    .store-summary-total {
        color: var(--store-dark);

        font-size: 1.35rem;
        font-weight: 900;
    }

    .store-checkout-button {
        width: 100%;

        display: flex;
        align-items: center;
        justify-content: space-between;

        padding: .8rem 1rem;

        border: 1px solid var(--store-primary);
        border-radius: 5px;

        background: var(--store-primary);
        color: var(--store-dark);

        font-weight: 800;
    }

    .store-checkout-button:hover {
        border-color: var(--store-primary-dark);
        background: var(--store-primary-dark);
        color: var(--store-dark);
    }

    @media (max-width: 767.98px) {
        .store-cart-image {
            height: 105px;
        }

        .store-cart-product-name {
            font-size: .88rem;
        }

        .store-quantity-control {
            max-width: 135px;
        }

        .store-cart-summary {
            position: static;
        }
    }

</style>
@endpush


@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {

    document.querySelectorAll('.quantity-form').forEach(function (form) {

        const input = form.querySelector('.quantity-input');
        const minusButton = form.querySelector('.quantity-minus');
        const plusButton = form.querySelector('.quantity-plus');

        if (!input || !minusButton || !plusButton) {
            return;
        }

        updateButtons(form);


        minusButton.addEventListener('click', function () {

            const min = Number(input.min) || 1;
            let value = Number(input.value) || min;

            if (value <= min) {
                return;
            }

            value--;

            input.value = value;

            updateButtons(form);

            updateCartItem(form, value);
        });


        plusButton.addEventListener('click', function () {

            const max = Number(input.max) || Infinity;
            let value = Number(input.value) || 1;

            if (value >= max) {
                return;
            }

            value++;

            input.value = value;

            updateButtons(form);

            updateCartItem(form, value);
        });


        input.addEventListener('change', function () {

            const min = Number(input.min) || 1;
            const max = Number(input.max) || Infinity;

            let value = Number(input.value) || min;

            value = Math.max(
                min,
                Math.min(value, max)
            );

            input.value = value;

            updateButtons(form);

            updateCartItem(form, value);
        });

    });


    async function updateCartItem(form, quantity) {

        const input = form.querySelector('.quantity-input');
        const minusButton = form.querySelector('.quantity-minus');
        const plusButton = form.querySelector('.quantity-plus');

        const csrfToken = document
            .querySelector('meta[name="csrf-token"]')
            ?.getAttribute('content');

        if (!csrfToken) {
            return;
        }

        input.disabled = true;
        minusButton.disabled = true;
        plusButton.disabled = true;

        form.classList.add('opacity-75');

        try {

            const response = await fetch(
                form.action,
                {
                    method: 'POST',

                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': csrfToken,
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },

                    body: new URLSearchParams({
                        _token: csrfToken,
                        _method: 'PATCH',
                        quantity: quantity,
                    }),
                }
            );


            const data = await response.json();


            if (!response.ok || !data.success) {
                throw new Error(
                    data.message ||
                    'Gagal memperbarui keranjang.'
                );
            }


            input.value = data.quantity;


            const subtotal = document.querySelector(
                `.cart-item-subtotal[data-item-id="${form.dataset.itemId}"]`
            );

            if (subtotal) {
                subtotal.textContent =
                    formatRupiah(data.subtotal);
            }


            const total = document.getElementById(
                'cart-total'
            );

            if (total) {
                total.textContent =
                    formatRupiah(data.total);
            }


            const itemCount = document.getElementById(
                'cart-item-count'
            );

            if (itemCount) {
                itemCount.textContent =
                    data.item_count;
            }


            document
                .querySelectorAll('.store-summary-total')
                .forEach(function (element) {
                    element.textContent =
                        formatRupiah(data.total);
                });


            if (typeof window.updateCartBadge === 'function') {
                window.updateCartBadge(
                    data.cart_count
                );
            }


            if (typeof window.showStoreNotification === 'function') {
                window.showStoreNotification(
                    data.message,
                    'success'
                );
            }

        } catch (error) {

            if (typeof window.showStoreNotification === 'function') {
                window.showStoreNotification(
                    error.message ||
                    'Terjadi kesalahan.',
                    'danger'
                );
            }

        } finally {

            input.disabled = false;

            form.classList.remove('opacity-75');

            updateButtons(form);
        }
    }


    function updateButtons(form) {

        const input = form.querySelector('.quantity-input');
        const minusButton = form.querySelector('.quantity-minus');
        const plusButton = form.querySelector('.quantity-plus');

        if (!input || !minusButton || !plusButton) {
            return;
        }

        const min = Number(input.min) || 1;
        const max = Number(input.max) || Infinity;
        const value = Number(input.value) || min;

        minusButton.disabled = value <= min;
        plusButton.disabled = value >= max;
    }


    function formatRupiah(value) {

        return 'Rp ' + Number(value).toLocaleString(
            'id-ID'
        );
    }

});
</script>
@endpush

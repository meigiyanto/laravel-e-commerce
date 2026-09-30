@extends('layouts.storefront')

@section('title', 'Keranjang - MeiStore')

@section('content')
<section class="container py-4 py-lg-5">
    <div class="mb-4">
        <h1 class="section-title mb-2">Sbopping Cart</h1>
        <p class="text-muted mb-0">Check again product that you wan to buy</p>
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
            <h2 class="h4 fw-bold mt-3">Cart is empty</h2>

            <p class="text-muted">Let's find your product that you want to buy.</p>
            <a
                href="{{ route('storefront.shop') }}"
                class="btn btn-primary"
            >
                <i class="bi bi-shop"></i>
                Start Shopping
            </a>
        </div>
    @else
        <a
            href="{{ route('storefront.shop') }}"
            class="btn btn-outline-primary"
        >
            <i class="bi bi-arrow-left"></i>
             Continue shopping
        </a>

        <div class="row g-4">
            {{-- Cart Items --}}
            <div class="col-lg-8">
                @foreach ($cart->items as $item)
                    <div class="card border-0 shadow-sm rounded-4 my-3">
                        <div class="card-body">
                            <div class="row g-3 align-items-center">
                                {{-- Image --}}
                                <div class="col-4 col-sm-3">
                                    <a href="{{ route('storefront.product', $item->product->slug) }}">
                                        @if ($item->product->image)
                                            <img src="{{ $item->product->image }}" alt="{{ $item->product->name }}" class="cart-product-image">
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

                                    <a href="{{ route('storefront.product', $item->product->slug) }}">
                                        <h3 class="h6 fw-bold text-dark mb-2">{{ $item->product->name }}</h3>
                                    </a>
                                    <div class="product-price">Rp {{ number_format($item->product->price, 0, ',', '.') }}</div>
                                </div>

                                {{-- Quantity --}}
                                <div class="col-7 col-sm-2">
                                    <form
                                        action="{{ route('cart.update', $item) }}"
                                        method="POST"
                                        class="quantity-form"
                                        data-item-id="{{ $item->id }}"
                                    >
                                        @csrf
                                        @method('PATCH')

                                        <label class="form-label small fw-semibold">
                                            Jumlah
                                        </label>

                                        <div class="input-group quantity-stepper">

                                            {{-- Minus --}}
                                            <button
                                                type="button"
                                                class="btn btn-outline-secondary quantity-minus"
                                                aria-label="Kurangi jumlah"
                                            >
                                                <i class="bi bi-dash"></i>
                                            </button>

                                            {{-- Quantity --}}
                                            <input
                                                type="number"
                                                name="quantity"
                                                class="form-control text-center quantity-input"
                                                value="{{ $item->quantity }}"
                                                min="1"
                                                max="{{ $item->product->stock }}"
                                                required
                                            >

                                            {{-- Plus --}}
                                            <button
                                                type="button"
                                                class="btn btn-outline-secondary quantity-plus"
                                                aria-label="Tambah jumlah"
                                            >
                                                <i class="bi bi-plus"></i>
                                            </button>

                                        </div>
                                    </form>
                                </div>

                                {{-- Delete --}}
                                <div class="col-5 col-sm-2 text-end">
                                    <div class="small text-muted mb-2">Subtotal</div>
                                    <div class="fw-bold mb-3 cart-item-subtotal" data-item-id="{{ $item->id }}">Rp {{ number_format($item->product->price * $item->quantity, 0, ',', '.') }}</div>
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
                        <h2 class="h5 fw-bold mb-4">Ringkasan Belanja</h2>

                        <div class="d-flex justify-content-between mb-3">
                            <span class="text-muted">Jumlah item</span>
                            <strong id="cart-item-count">{{ $cart->items->sum('quantity') }}</strong>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between mb-4">
                            <span class="fw-bold">Total</span>
                            <span class="product-price" id="cart-total">Rp {{ number_format($total, 0, ',', '.') }}</span>
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
    .quantity-stepper .quantity-minus,
    .quantity-stepper .quantity-plus {
        width: 38px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .quantity-stepper .quantity-input::-webkit-inner-spin-button,
    .quantity-stepper .quantity-input::-webkit-outer-spin-button {
        -webkit-appearance: none;
        margin: 0;
    }

    .quantity-stepper .quantity-input {
        -moz-appearance: textfield;
    }

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

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {

    /*
    |--------------------------------------------------------------------------
    | Quantity Stepper
    |--------------------------------------------------------------------------
    */

    document.querySelectorAll('.quantity-form').forEach(function (form) {

        const input = form.querySelector('.quantity-input');
        const minusButton = form.querySelector('.quantity-minus');
        const plusButton = form.querySelector('.quantity-plus');

        if (!input || !minusButton || !plusButton) {
            return;
        }

        const min = Number(input.min) || 1;
        const max = Number(input.max) || Infinity;

        function updateButtons() {
            const value = Number(input.value) || min;

            minusButton.disabled = value <= min;
            plusButton.disabled = value >= max;
        }

        /*
        |--------------------------------------------------------------------------
        | Minus
        |--------------------------------------------------------------------------
        */

        minusButton.addEventListener('click', function () {
            if (input.disabled) {
                return;
            }

            let value = Number(input.value) || min;

            if (value <= min) {
                return;
            }

            value--;

            input.value = value;

            updateButtons();

            updateCartItem(form, value);
        });


        /*
        |--------------------------------------------------------------------------
        | Plus
        |--------------------------------------------------------------------------
        */

        plusButton.addEventListener('click', function () {
            if (input.disabled) {
                return;
            }

            let value = Number(input.value) || min;

            if (value >= max) {
                return;
            }

            value++;

            input.value = value;

            updateButtons();

            updateCartItem(form, value);
        });


        /*
        |--------------------------------------------------------------------------
        | Manual Input
        |--------------------------------------------------------------------------
        */

        input.addEventListener('change', function () {
            let value = Number(input.value) || min;

            value = Math.max(
                min,
                Math.min(value, max)
            );

            input.value = value;

            updateButtons();

            updateCartItem(form, value);
        });


        updateButtons();
    });


    /*
    |--------------------------------------------------------------------------
    | AJAX Update Cart
    |--------------------------------------------------------------------------
    */

    async function updateCartItem(form, quantity) {

        const input = form.querySelector('.quantity-input');
        const minusButton = form.querySelector('.quantity-minus');
        const plusButton = form.querySelector('.quantity-plus');

        if (!input) {
            return;
        }

        /*
        |--------------------------------------------------------------
        | Disable Controls
        |--------------------------------------------------------------
        */

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
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },

                    body: new URLSearchParams({
                        '_token': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        '_method': 'PATCH',
                        'quantity': quantity,
                    }),
                }
            );

            const data = await response.json();

            /*
            |--------------------------------------------------------------
            | Error
            |--------------------------------------------------------------
            */

            if (!response.ok || !data.success) {
                throw new Error(
                    data.message ||
                    'Gagal memperbarui jumlah produk.'
                );
            }


            /*
            |--------------------------------------------------------------
            | Update Quantity
            |--------------------------------------------------------------
            */

            input.value = data.quantity;


            /*
            |--------------------------------------------------------------
            | Update Subtotal
            |--------------------------------------------------------------
            */

            const subtotal = document.querySelector(
                `.cart-item-subtotal[data-item-id="${form.dataset.itemId}"]`
            );

            if (subtotal) {
                subtotal.textContent =
                    formatRupiah(data.subtotal);
            }


            /*
            |--------------------------------------------------------------
            | Update Total
            |--------------------------------------------------------------
            */

            const total = document.getElementById(
                'cart-total'
            );

            if (total) {
                total.textContent =
                    formatRupiah(data.total);
            }


            /*
            |--------------------------------------------------------------
            | Update Item Count
            |--------------------------------------------------------------
            */

            const itemCount = document.getElementById(
                'cart-item-count'
            );

            if (itemCount) {
                itemCount.textContent =
                    data.item_count;
            }


            /*
            |--------------------------------------------------------------
            | Update Navbar Cart Badge
            |--------------------------------------------------------------
            */

            updateCartBadge(
                data.cart_count
            );


            /*
            |--------------------------------------------------------------
            | Success Notification
            |--------------------------------------------------------------
            */

            showCartNotification(
                data.message,
                'success'
            );

        } catch (error) {

            showCartNotification(
                error.message ||
                'Terjadi kesalahan.',
                'danger'
            );

        } finally {

            input.disabled = false;

            form.classList.remove('opacity-75');

            updateStepperButtons(form);
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Stepper Button State
    |--------------------------------------------------------------------------
    */

    function updateStepperButtons(form) {

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


    /*
    |--------------------------------------------------------------------------
    | Format Rupiah
    |--------------------------------------------------------------------------
    */

    function formatRupiah(value) {

        return 'Rp ' + Number(value).toLocaleString(
            'id-ID'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Cart Badge
    |--------------------------------------------------------------------------
    */

    function updateCartBadge(count) {
        const badge = document.getElementById(
            'cart-count-badge'
        );

        if (!badge) {
            return;
        }

        const cartCount = Number(count) || 0;

        badge.textContent = cartCount;

        if (cartCount > 0) {
            badge.classList.remove('d-none');
        } else {
            badge.classList.add('d-none');
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Notification
    |--------------------------------------------------------------------------
    */

    function showCartNotification(message, type) {
        let container = document.getElementById(
            'cart-notification-container'
        );

        if (!container) {
            container = document.createElement('div');
            container.id = 'cart-notification-container';
            container.className = 'position-fixed top-0 end-0 p-3';
            container.style.zIndex = '1080';
            document.body.appendChild(container);
        }

        const alert = document.createElement('div');

        alert.className = `alert alert-${type} alert-dismissible fade show shadow-sm`;
        alert.setAttribute('role', 'alert');
        alert.innerHTML = `
            <i class="bi bi-${
                type === 'success'
                    ? 'check-circle'
                    : 'exclamation-circle'
            } me-2"></i>

            ${message}

            <button
                type="button"
                class="btn-close"
                data-bs-dismiss="alert"
                aria-label="Close"
            ></button>
        `;

        container.appendChild(alert);

        setTimeout(function () {
            alert.classList.remove('show');
            setTimeout(function () {
                alert.remove();
            }, 150);
        }, 2500);
    }
});
</script>
@endpush

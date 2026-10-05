@extends('layouts.storefront')

@section('title', $category->name . ' - MeiStore')

@section('content')
    {{-- =========================================================
         BREADCRUMB
    ========================================================== --}}
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

                    <li class="breadcrumb-item">
                        <a href="{{ route('storefront.shop') }}">
                            Shop
                        </a>
                    </li>

                    <li
                        class="breadcrumb-item active"
                        aria-current="page"
                    >
                        {{ $category->name }}
                    </li>

                </ol>

            </nav>
        </div>
    </div>


    {{-- =========================================================
         CATEGORY HEADER
    ========================================================== --}}
    <section class="store-section pb-3">

        <div class="container">

            <div class="row align-items-end g-3">

                <div class="col-lg-8">

                    <span
                        class="d-inline-flex align-items-center gap-2 mb-2"
                        style="
                            color: var(--store-primary-dark);
                            font-size: .78rem;
                            font-weight: 800;
                            text-transform: uppercase;
                            letter-spacing: .06em;
                        "
                    >
                        <i class="bi bi-grid"></i>
                        Kategori Produk
                    </span>

                    <h1
                        class="mb-2"
                        style="
                            color: var(--store-dark);
                            font-size: clamp(1.8rem, 4vw, 2.6rem);
                            font-weight: 900;
                            letter-spacing: -.035em;
                        "
                    >
                        {{ $category->name }}
                    </h1>

                    <p class="text-muted mb-0">
                        Temukan berbagai produk dalam kategori
                        {{ $category->name }}.
                    </p>

                </div>

                <div class="col-lg-4 text-lg-end">

                    <span class="text-muted small">
                        <strong class="text-dark">
                            {{ $products->total() }}
                        </strong>

                        produk tersedia
                    </span>

                </div>

            </div>

        </div>

    </section>


    {{-- =========================================================
         PRODUCT LIST
    ========================================================== --}}
    <section class="store-section pt-3">

        <div class="container">

            {{-- Toolbar --}}
            <div
                class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4 p-3"
                style="
                    border: 1px solid var(--store-border);
                    border-radius: var(--store-radius);
                    background: #fff;
                "
            >

                <div class="d-flex align-items-center gap-2">

                    <i class="bi bi-grid-3x3-gap text-muted"></i>

                    <span class="small text-muted">
                        Menampilkan
                        <strong class="text-dark">
                            {{ $products->firstItem() ?? 0 }}
                        </strong>
                        -
                        <strong class="text-dark">
                            {{ $products->lastItem() ?? 0 }}
                        </strong>
                        dari
                        <strong class="text-dark">
                            {{ $products->total() }}
                        </strong>
                        produk
                    </span>

                </div>


                <div class="d-flex align-items-center gap-2">

                    <span class="small text-muted d-none d-sm-inline">
                        Urutkan:
                    </span>

                    <select
                        class="form-select form-select-sm"
                        style="width: auto; min-width: 150px;"
                        onchange="changeCategorySort(this.value)"
                    >
                        <option
                            value=""
                            {{ request('sort') === null ? 'selected' : '' }}
                        >
                            Default
                        </option>

                        <option
                            value="latest"
                            {{ request('sort') === 'latest' ? 'selected' : '' }}
                        >
                            Terbaru
                        </option>

                        <option
                            value="price_low"
                            {{ request('sort') === 'price_low' ? 'selected' : '' }}
                        >
                            Harga terendah
                        </option>

                        <option
                            value="price_high"
                            {{ request('sort') === 'price_high' ? 'selected' : '' }}
                        >
                            Harga tertinggi
                        </option>

                    </select>

                </div>

            </div>


            {{-- =================================================
                 PRODUCTS
            ================================================== --}}
            @if ($products->count())
                <div class="row g-3 g-lg-4">
                    @foreach ($products as $product)
                        <div class="col-6 col-md-4 col-lg-3">
                            <x-store-product-card :product="$product" />
                        </div>
                    @endforeach
                </div>

                {{-- =================================================
                     PAGINATION
                ================================================== --}}
                @if ($products->hasPages())

                    <div class="d-flex justify-content-center mt-5">

                        {{ $products->withQueryString()->links() }}

                    </div>

                @endif

            @else

                {{-- =================================================
                     EMPTY STATE
                ================================================== --}}
                <div
                    class="text-center py-5 px-3"
                    style="
                        border: 1px solid var(--store-border);
                        border-radius: var(--store-radius);
                        background: var(--store-light);
                    "
                >

                    <div
                        class="mx-auto mb-3"
                        style="
                            width: 72px;
                            height: 72px;
                            display: flex;
                            align-items: center;
                            justify-content: center;
                            border-radius: 50%;
                            background: #fff;
                            color: #adb5bd;
                            font-size: 2rem;
                        "
                    >
                        <i class="bi bi-box-seam"></i>
                    </div>

                    <h2
                        class="h5 fw-bold mb-2"
                        style="color: var(--store-dark);"
                    >
                        Belum Ada Produk
                    </h2>

                    <p class="text-muted mb-4">
                        Belum ada produk yang tersedia
                        dalam kategori ini.
                    </p>

                    <a
                        href="{{ route('storefront.shop') }}"
                        class="store-btn-primary"
                    >
                        <i class="bi bi-shop"></i>
                        Lihat Semua Produk
                    </a>

                </div>

            @endif

        </div>

    </section>

@endsection


@push('scripts')

<script>
    /**
     * Change category sorting without
     * losing the current category.
     */
    function changeCategorySort(value) {
        const url = new URL(window.location.href);

        if (value) {
            url.searchParams.set('sort', value);
        } else {
            url.searchParams.delete('sort');
        }

        url.searchParams.delete('page');

        window.location.href = url.toString();
    }


    /**
     * AJAX add-to-cart.
     */
    document.addEventListener('DOMContentLoaded', function () {

        const forms = document.querySelectorAll(
            '.category-add-cart-form'
        );

        forms.forEach(function (form) {

            form.addEventListener('submit', async function (event) {

                event.preventDefault();

                const button =
                    form.querySelector('button[type="submit"]');

                if (!button) {
                    return;
                }

                const originalHTML = button.innerHTML;

                button.disabled = true;

                button.innerHTML = `
                    <span
                        class="spinner-border spinner-border-sm"
                        role="status"
                        aria-hidden="true"
                    ></span>

                    Menambahkan...
                `;


                try {

                    const response = await fetch(
                        form.action,
                        {
                            method: 'POST',

                            headers: {
                                'X-CSRF-TOKEN':
                                    document
                                        .querySelector(
                                            'meta[name="csrf-token"]'
                                        )
                                        .getAttribute('content'),

                                'Accept':
                                    'application/json',

                                'X-Requested-With':
                                    'XMLHttpRequest'
                            },

                            body: new FormData(form)
                        }
                    );


                    const data =
                        await response.json();


                    if (!response.ok) {
                        throw new Error(
                            data.message ||
                            'Gagal menambahkan produk.'
                        );
                    }


                    /*
                     * Update cart badge from
                     * the response when available.
                     */
                    if (
                        typeof window.updateCartBadge ===
                        'function'
                    ) {
                        if (
                            typeof data.cart_count !==
                            'undefined'
                        ) {
                            window.updateCartBadge(
                                data.cart_count
                            );
                        }
                    }


                    /*
                     * Show global notification.
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
                    }

                } finally {

                    button.disabled = false;

                    button.innerHTML =
                        originalHTML;

                }

            });

        });

    });
</script>

@endpush

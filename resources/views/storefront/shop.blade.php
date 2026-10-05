@extends('layouts.storefront')

@section('title', 'Shop - MeiStore')

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
                    Shop
                </li>

            </ol>
        </nav>
    </div>
</div>


{{-- =========================================================
     SHOP HEADER
========================================================= --}}
<section class="store-section pb-3">
    <div class="container">

        <div class="store-section-header">

            <div>
                <h1 class="store-section-title">
                    Semua Produk
                </h1>

                <p class="store-section-subtitle">
                    Temukan berbagai produk terbaik di MeiStore.
                </p>
            </div>

            <div class="text-muted small">
                <i class="bi bi-box-seam me-1"></i>
                {{ $products->total() }} produk
            </div>

        </div>

    </div>
</section>


{{-- =========================================================
     FILTER
========================================================= --}}
<section class="pb-4">
    <div class="container">

        <div class="shop-filter-card">

            <form
                id="shop-filter-form"
                action="{{ route('storefront.shop') }}"
                method="GET"
            >

                <div class="row g-3">

                    {{-- Search --}}
                    <div class="col-12 col-lg-4">

                        <label
                            for="shop-search"
                            class="form-label fw-bold small"
                        >
                            Cari Produk
                        </label>

                        <div class="input-group">

                            <span class="input-group-text bg-white">
                                <i class="bi bi-search text-muted"></i>
                            </span>

                            <input
                                type="search"
                                id="shop-search"
                                name="q"
                                class="form-control"
                                value="{{ request('q') }}"
                                placeholder="Nama atau deskripsi produk..."
                            >

                        </div>

                    </div>


                    {{-- Category --}}
                    <div class="col-12 col-md-6 col-lg-2">

                        <label
                            for="shop-category"
                            class="form-label fw-bold small"
                        >
                            Kategori
                        </label>

                        <select
                            id="shop-category"
                            name="category"
                            class="form-select"
                        >

                            <option value="">
                                Semua Kategori
                            </option>

                            @foreach ($categories as $category)

                                <option
                                    value="{{ $category->slug }}"
                                    @selected(request('category') === $category->slug)
                                >
                                    {{ $category->name }}
                                </option>

                            @endforeach

                        </select>

                    </div>


                    {{-- Subcategory --}}
                    <div class="col-12 col-md-6 col-lg-2">

                        <label
                            for="shop-subcategory"
                            class="form-label fw-bold small"
                        >
                            Subkategori
                        </label>

                        <select
                            id="shop-subcategory"
                            name="subcategory"
                            class="form-select"
                            {{ request('category') ? '' : 'disabled' }}
                        >

                            <option value="">
                                {{ request('category')
                                    ? 'Semua Subkategori'
                                    : 'Pilih kategori dahulu' }}
                            </option>

                            @foreach ($categories as $category)

                                @foreach ($category->subCategories as $subCategory)

                                    <option
                                        value="{{ $subCategory->slug }}"
                                        data-category="{{ $category->slug }}"
                                        @selected(
                                            request('subcategory') === $subCategory->slug &&
                                            request('category') === $category->slug
                                        )
                                    >
                                        {{ $subCategory->name }}
                                    </option>

                                @endforeach

                            @endforeach

                        </select>

                    </div>


                    {{-- Sorting --}}
                    <div class="col-12 col-md-6 col-lg-2">

                        <label
                            for="shop-sort"
                            class="form-label fw-bold small"
                        >
                            Urutkan
                        </label>

                        <select
                            id="shop-sort"
                            name="sort"
                            class="form-select"
                        >

                            <option
                                value=""
                                @selected(! request('sort'))
                            >
                                Terbaru
                            </option>

                            <option
                                value="oldest"
                                @selected(request('sort') === 'oldest')
                            >
                                Terlama
                            </option>

                            <option
                                value="price_low"
                                @selected(request('sort') === 'price_low')
                            >
                                Harga Terendah
                            </option>

                            <option
                                value="price_high"
                                @selected(request('sort') === 'price_high')
                            >
                                Harga Tertinggi
                            </option>

                            <option
                                value="name_asc"
                                @selected(request('sort') === 'name_asc')
                            >
                                Nama A - Z
                            </option>

                            <option
                                value="name_desc"
                                @selected(request('sort') === 'name_desc')
                            >
                                Nama Z - A
                            </option>

                        </select>

                    </div>


                    {{-- Actions --}}
                    <div class="col-12 col-md-6 col-lg-2">

                        <label class="form-label fw-bold small d-block">
                            &nbsp;
                        </label>

                        <div class="d-flex gap-2">

                            <button
                                type="submit"
                                class="store-add-cart flex-grow-1"
                            >
                                <i class="bi bi-funnel"></i>
                                Terapkan
                            </button>

                            @if (request()->hasAny([
                                'q',
                                'category',
                                'subcategory',
                                'sort',
                            ]))

                                <a
                                    href="{{ route('storefront.shop') }}"
                                    class="btn btn-outline-secondary"
                                    title="Reset filter"
                                >
                                    <i class="bi bi-arrow-counterclockwise"></i>
                                </a>

                            @endif

                        </div>

                    </div>

                </div>

            </form>

        </div>

    </div>
</section>


{{-- =========================================================
     ACTIVE FILTERS
========================================================= --}}
@if (request()->hasAny([
    'q',
    'category',
    'subcategory',
    'sort',
]))

<section class="pb-3">

    <div class="container">

        <div class="d-flex flex-wrap align-items-center gap-2">

            <span class="small text-muted fw-semibold">
                Filter aktif:
            </span>


            {{-- Search --}}
            @if (request('q'))

                <span class="badge rounded-pill text-bg-light border text-dark px-3 py-2">

                    <i class="bi bi-search me-1"></i>

                    {{ request('q') }}

                </span>

            @endif


            {{-- Category --}}
            @if (request('category'))

                @php
                    $selectedCategory = $categories->firstWhere(
                        'slug',
                        request('category')
                    );
                @endphp

                @if ($selectedCategory)

                    <span class="badge rounded-pill text-bg-light border text-dark px-3 py-2">

                        <i class="bi bi-grid me-1"></i>

                        {{ $selectedCategory->name }}

                    </span>

                @endif

            @endif


            {{-- Subcategory --}}
            @if (request('subcategory'))

                @php
                    $selectedSubCategory = null;

                    foreach ($categories as $category) {
                        $match = $category->subCategories->firstWhere(
                            'slug',
                            request('subcategory')
                        );

                        if ($match) {
                            $selectedSubCategory = $match;
                            break;
                        }
                    }
                @endphp

                @if ($selectedSubCategory)

                    <span class="badge rounded-pill text-bg-light border text-dark px-3 py-2">

                        <i class="bi bi-diagram-3 me-1"></i>

                        {{ $selectedSubCategory->name }}

                    </span>

                @endif

            @endif


            {{-- Sorting --}}
            @php
                $sortLabels = [
                    'oldest' => 'Terlama',
                    'price_low' => 'Harga Terendah',
                    'price_high' => 'Harga Tertinggi',
                    'name_asc' => 'Nama A - Z',
                    'name_desc' => 'Nama Z - A',
                ];
            @endphp

            @if (request('sort'))

                <span class="badge rounded-pill text-bg-light border text-dark px-3 py-2">

                    <i class="bi bi-sort-down me-1"></i>

                    {{ $sortLabels[request('sort')] ?? 'Terbaru' }}

                </span>

            @endif


            <a
                href="{{ route('storefront.shop') }}"
                class="btn btn-sm btn-outline-secondary rounded-pill"
            >
                <i class="bi bi-x-lg me-1"></i>
                Reset
            </a>

        </div>

    </div>

</section>

@endif


{{-- =========================================================
     RESULT HEADER
========================================================= --}}
<section class="pb-3">

    <div class="container">

        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">

            <div>

                @if ($products->total() > 0)

                    <span class="small text-muted">

                        Menampilkan

                        <strong class="text-dark">
                            {{ $products->firstItem() }}
                        </strong>

                        -

                        <strong class="text-dark">
                            {{ $products->lastItem() }}
                        </strong>

                        dari

                        <strong class="text-dark">
                            {{ $products->total() }}
                        </strong>

                        produk

                    </span>

                @else

                    <span class="small text-muted">
                        Tidak ada produk ditemukan.
                    </span>

                @endif

            </div>

        </div>

    </div>

</section>


{{-- =========================================================
     PRODUCTS
========================================================= --}}
<section class="pb-5">

    <div class="container">

        @if ($products->count())

            <div class="row g-3 g-md-4">

                @foreach ($products as $product)

                    <div class="col-6 col-md-4 col-lg-3">

                        <x-store-product-card
                            :product="$product"
                        />

                    </div>

                @endforeach

            </div>


            {{-- =================================================
                 PAGINATION
            ================================================== --}}
            @if ($products->hasPages())

                <div class="d-flex justify-content-center mt-5">

                    {{ $products
                        ->onEachSide(1)
                        ->links('pagination::bootstrap-5') }}

                </div>

            @endif

        @else

            {{-- Empty state --}}
            <div class="shop-empty-state">

                <div class="shop-empty-icon">
                    <i class="bi bi-search"></i>
                </div>

                <h2 class="h5 fw-bold mb-2">
                    Produk Tidak Ditemukan
                </h2>

                <p class="text-muted mb-4">
                    Tidak ada produk yang sesuai dengan filter yang dipilih.
                </p>

                <a
                    href="{{ route('storefront.shop') }}"
                    class="store-btn-primary"
                >
                    <i class="bi bi-arrow-counterclockwise"></i>
                    Reset Filter
                </a>

            </div>

        @endif

    </div>

</section>

@endsection


@push('styles')

<style>
    .shop-filter-card {
        padding: 1.25rem;
        border: 1px solid var(--store-border);
        border-radius: var(--store-radius);
        background: #fff;
        box-shadow: 0 4px 18px rgba(0, 0, 0, .035);
    }

    .shop-filter-card .form-label {
        color: var(--store-dark);
    }

    .shop-filter-card .form-select:disabled {
        background-color: #f5f5f5;
        color: #8a8f98;
        cursor: not-allowed;
    }

    .shop-empty-state {
        padding: 4rem 1.5rem;
        text-align: center;
        border: 1px solid var(--store-border);
        border-radius: var(--store-radius);
        background: var(--store-light);
    }

    .shop-empty-icon {
        width: 72px;
        height: 72px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 1rem;
        border-radius: 50%;
        background: #fff;
        color: #adb5bd;
        font-size: 2rem;
    }

    @media (max-width: 575.98px) {
        .shop-filter-card {
            padding: 1rem;
        }
    }
</style>

@endpush


@push('scripts')

<script>
document.addEventListener('DOMContentLoaded', function () {

    const filterForm = document.getElementById(
        'shop-filter-form'
    );

    const categorySelect = document.getElementById(
        'shop-category'
    );

    const subCategorySelect = document.getElementById(
        'shop-subcategory'
    );


    /*
    |--------------------------------------------------------------------------
    | Category → Subcategory
    |--------------------------------------------------------------------------
    */

    function updateSubCategories(resetValue = false) {

        const selectedCategory =
            categorySelect.value;

        const currentSubCategory =
            subCategorySelect.value;

        const options =
            subCategorySelect.querySelectorAll(
                'option[data-category]'
            );


        /*
         * Reset state.
         */
        subCategorySelect.disabled =
            selectedCategory === '';


        /*
         * Update placeholder.
         */
        const placeholder =
            subCategorySelect.querySelector(
                'option:not([data-category])'
            );

        if (placeholder) {

            placeholder.textContent =
                selectedCategory
                    ? 'Semua Subkategori'
                    : 'Pilih kategori dahulu';

        }


        /*
         * Show only subcategories belonging
         * to the selected category.
         */
        options.forEach(function (option) {

            const belongsToCategory =
                selectedCategory !== '' &&
                option.dataset.category === selectedCategory;

            option.hidden =
                !belongsToCategory;

            option.disabled =
                !belongsToCategory;

        });


        /*
         * If category changed, remove an old
         * subcategory that no longer belongs
         * to the selected category.
         */
        if (
            resetValue ||
            selectedCategory === ''
        ) {

            subCategorySelect.value = '';

            return;
        }


        const selectedOption =
            Array.from(options).find(function (option) {

                return (
                    option.value === currentSubCategory &&
                    option.dataset.category === selectedCategory
                );

            });


        if (!selectedOption) {
            subCategorySelect.value = '';
        }

    }


    /*
    |--------------------------------------------------------------------------
    | Initial state
    |--------------------------------------------------------------------------
    */

    updateSubCategories(false);


    /*
    |--------------------------------------------------------------------------
    | Category changed
    |--------------------------------------------------------------------------
    */

    categorySelect.addEventListener(
        'change',
        function () {

            /*
             * Category has changed, therefore
             * previous subcategory is no longer
             * trustworthy.
             */
            updateSubCategories(true);

        }
    );


    /*
    |--------------------------------------------------------------------------
    | Submit
    |--------------------------------------------------------------------------
    */

    filterForm.addEventListener(
        'submit',
        function () {

            /*
             * Do not send an empty subcategory.
             */
            if (
                subCategorySelect.disabled ||
                subCategorySelect.value === ''
            ) {

                subCategorySelect.disabled = true;

            }

        }
    );


    /*
    |--------------------------------------------------------------------------
    | Add To Cart
    |--------------------------------------------------------------------------
    */

    document
        .querySelectorAll('.add-to-cart-form')
        .forEach(function (form) {

            form.addEventListener(
                'submit',
                async function (event) {

                    event.preventDefault();

                    const button =
                        form.querySelector(
                            '.add-to-cart-button'
                        );

                    if (!button) {
                        return;
                    }

                    const originalHtml =
                        button.innerHTML;

                    button.disabled = true;

                    button.innerHTML = `
                        <span
                            class="spinner-border spinner-border-sm"
                            role="status"
                            aria-hidden="true"
                        ></span>
                        <span>Menambahkan...</span>
                    `;


                    try {

                        const response =
                            await fetch(
                                form.action,
                                {
                                    method: 'POST',

                                    headers: {
                                        'Accept':
                                            'application/json',

                                        'X-Requested-With':
                                            'XMLHttpRequest',

                                        'X-CSRF-TOKEN':
                                            document
                                                .querySelector(
                                                    'meta[name="csrf-token"]'
                                                )
                                                .getAttribute('content'),
                                    },

                                    body:
                                        new FormData(form),
                                }
                            );


                        const data =
                            await response.json();


                        if (
                            !response.ok ||
                            !data.success
                        ) {

                            throw new Error(
                                data.message ||
                                'Gagal menambahkan produk ke keranjang.'
                            );

                        }


                        if (
                            typeof window.updateCartBadge ===
                            'function'
                        ) {

                            window.updateCartBadge(
                                data.cart_count
                            );

                        }


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

                        } else {

                            alert(
                                error.message ||
                                'Terjadi kesalahan.'
                            );

                        }

                    } finally {

                        button.disabled = false;

                        button.innerHTML =
                            originalHtml;

                    }

                }
            );

        });


    /*
    |--------------------------------------------------------------------------
    | Wishlist
    |--------------------------------------------------------------------------
    */

    document
        .querySelectorAll('.wishlist-form')
        .forEach(function (form) {

            form.addEventListener(
                'submit',
                async function (event) {

                    event.preventDefault();

                    const button =
                        form.querySelector('button');

                    if (!button) {
                        return;
                    }

                    button.disabled = true;

                    const originalHtml =
                        button.innerHTML;

                    button.innerHTML =
                        '<span class="spinner-border spinner-border-sm"></span>';


                    try {

                        const response =
                            await fetch(
                                form.action,
                                {
                                    method: 'POST',

                                    headers: {
                                        'Accept':
                                            'application/json',

                                        'X-Requested-With':
                                            'XMLHttpRequest',

                                        'X-CSRF-TOKEN':
                                            document
                                                .querySelector(
                                                    'meta[name="csrf-token"]'
                                                )
                                                .getAttribute('content'),
                                    },

                                    body:
                                        new FormData(form),
                                }
                            );


                        const data =
                            await response.json();


                        if (!response.ok) {

                            throw new Error(
                                data.message ||
                                'Gagal menambahkan wishlist.'
                            );

                        }


                        if (
                            typeof window.showStoreNotification ===
                            'function'
                        ) {

                            window.showStoreNotification(
                                data.message ||
                                'Produk ditambahkan ke wishlist.',
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
                            originalHtml;

                    }

                }
            );

        });


    /*
    |--------------------------------------------------------------------------
    | Compare
    |--------------------------------------------------------------------------
    */

    document
        .querySelectorAll('.compare-form')
        .forEach(function (form) {

            form.addEventListener(
                'submit',
                async function (event) {

                    event.preventDefault();

                    const button =
                        form.querySelector('button');

                    if (!button) {
                        return;
                    }

                    button.disabled = true;

                    const originalHtml =
                        button.innerHTML;

                    button.innerHTML =
                        '<span class="spinner-border spinner-border-sm"></span>';


                    try {

                        const response =
                            await fetch(
                                form.action,
                                {
                                    method: 'POST',

                                    headers: {
                                        'Accept':
                                            'application/json',

                                        'X-Requested-With':
                                            'XMLHttpRequest',

                                        'X-CSRF-TOKEN':
                                            document
                                                .querySelector(
                                                    'meta[name="csrf-token"]'
                                                )
                                                .getAttribute('content'),
                                    },

                                    body:
                                        new FormData(form),
                                }
                            );


                        const data =
                            await response.json();


                        if (!response.ok) {

                            throw new Error(
                                data.message ||
                                'Gagal menambahkan produk ke compare.'
                            );

                        }


                        if (
                            typeof window.showStoreNotification ===
                            'function'
                        ) {

                            window.showStoreNotification(
                                data.message ||
                                'Produk ditambahkan ke compare.',
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
                            originalHtml;

                    }

                }
            );

        });

});
</script>

@endpush

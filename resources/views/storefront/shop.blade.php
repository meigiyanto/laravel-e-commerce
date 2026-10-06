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

@php
    $selectedCategorySlug = request('category');
    $selectedSubCategorySlug = $categories->firstWhere('slug', request('subcategory'));
@endphp

{{-- =========================================================
     SHOP HEADER
========================================================= --}}
<section class="store-section pb-3">
    <div class="container">
        <div class="store-section-header">
            <div>
                <h1 class="store-section-title">All Product<h1>
                <p class="store-section-subtitle">Find the best products here</p>
            </div>

            <div class="text-muted small">
                <i class="bi bi-box-seam me-1"></i>
                {{ $products->total() }} product
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
                class="store-filter-form"
                action="{{ route('storefront.shop') }}"
                method="GET"
            >

                <div class="row g-3">
                    {{-- Search --}}
                    <div class="col-12 col-lg-4">
                        <label for="shop-search" class="form-label fw-bold small">
Search Product</label>

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
                                placeholder="Name or description product..."
                            >
                        </div>
                    </div>

                    <div class="col-12 col-md-6">
                        <label for="category" class="form-label fw-bold small">
Category</label>
                        {{-- Category --}}
                        <select id="category" name="category" class="form-select">
                            <option value="">All Category</option>
                            @foreach ($categories as $item)
                                <option value="{{ $item->slug }}" @selected($selectedCategorySlug === $item->slug)>{{ $item->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-12 col-md-6">
                        {{-- Subcategory --}}
                        <label for="subcategory" class="form-label fw-bold small">
Sub Category</label>
                        <select id="subcategory" name="subcategory" class="form-select" {{ $selectedCategorySlug ? '' : 'disabled' }}>
                            <option value="">{{ $selectedCategorySlug ? 'All Subcategory' : 'Choose category first' }}</option>
                            @foreach ($categories as $item)
                                @foreach ($item->subCategories as $subCategory)
                                    <option value="{{ $subCategory->slug }}" data-category="{{ $item->slug }}" @selected($selectedSubCategorySlug === $subCategory->slug && $selectedCategorySlug === $item->slug)>
                                        {{ $subCategory->name }}
                                    </option>
                                @endforeach
                            @endforeach
                        </select>
                    </div>

                    {{-- Sorting --}}
                    <div class="col-12 col-md-6 col-lg-2">
                        <label for="shop-sort" class="form-label fw-bold small">Urutkan</label>
                        <select id="shop-sort" name="sort" class="form-select">
                            <option value="" @selected(!request('sort'))>Newest</option>
                            <option value="oldest" @selected(request('sort') === 'oldest')>Oldest</option>
                            <option value="price_low" @selected(request('sort') === 'price_low')>
Lower Price</option>
                            <option value="price_high" @selected(request('sort') === 'price_high')>Higher Price</option>
                            <option value="name_asc" @selected(request('sort') === 'name_asc')>Name A - Z</option>
                            <option value="name_desc" @selected(request('sort') === 'name_desc')> Name Z - A</option>
                        </select>

                    </div>


                    {{-- Actions --}}
                    <div class="col-12 col-md-6 col-lg-2">
                        <label class="form-label fw-bold small d-block">&nbsp;</label>
                            <div class="d-flex gap-2">
                            <button type="submit" class="store-add-cart flex-grow-1">
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
            <span class="small text-muted fw-semibold">Filter aktif:</span>


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
                    $selectedCategory = $categories->firstWhere('slug', request('category'));
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
                        $match = $category->subCategories->firstWhere('slug', request('subcategory'));

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
                        Showing
                        <strong class="text-dark">{{ $products->firstItem() }}</strong>
                        -
                        <strong class="text-dark">{{ $products->lastItem() }}</strong>
                        of
                        <strong class="text-dark">{{ $products->total() }}</strong>
                        product
                    </span>
                @else
                    <span class="small text-muted">
                        Product not found
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
                        <x-store-product-card :product="$product"/>
                    </div>
                @endforeach
            </div>
            {{-- =================================================
                 PAGINATION
            ================================================== --}}
            @if ($products->hasPages())
                <div class="d-flex justify-content-center mt-5">
                    {{ $products ->onEachSide(1)->links('pagination::bootstrap-5') }}
                </div>
            @endif
        @else
            {{-- Empty state --}}
            <div class="shop-empty-state">
                <div class="shop-empty-icon"><i class="bi bi-search"></i></div>

                <h2 class="h5 fw-bold mb-2">Produk Tidak Ditemukan<h2>
                <p class="text-muted mb-4">Tidak ada produk yang sesuai dengan filter yang dipilih.</p>
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

@once
    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {

                document.querySelectorAll('.store-filter-form').forEach(function (form) {

                    const categorySelect = form.querySelector('[name="category"]');
                    const subCategorySelect = form.querySelector('[name="subcategory"]');

                    if (!subCategorySelect) {
                        return;
                    }

                    function syncSubCategories() {

                        /*
                         * Pada halaman category:
                         * categorySelect tidak tersedia karena
                         * kategori sudah ditentukan oleh URL.
                         */
                        if (!categorySelect) {
                            subCategorySelect.disabled = false;

                            return;
                        }


                        const selectedCategory = categorySelect.value.trim();


                        /*
                         * Tidak ada kategori:
                         * subkategori harus disabled.
                         */
                        if (selectedCategory === '') {

                            subCategorySelect.value = '';
                            subCategorySelect.disabled = true;

                            subCategorySelect
                                .querySelectorAll('option[data-category]')
                                .forEach(function (option) {
                                    option.hidden = true;
                                });

                            return;
                        }


                        /*
                         * Ada kategori:
                         * subkategori harus aktif.
                         */
                        subCategorySelect.disabled = false;


                        let selectedSubCategoryIsValid = false;


                        subCategorySelect
                            .querySelectorAll('option[data-category]')
                            .forEach(function (option) {

                                const belongsToCategory =
                                    option.dataset.category ===
                                    selectedCategory;


                                option.hidden =
                                    !belongsToCategory;


                                /*
                                 * Pastikan selected subcategory
                                 * memang berasal dari kategori
                                 * yang sedang dipilih.
                                 */
                                if (
                                    option.selected &&
                                    belongsToCategory
                                ) {
                                    selectedSubCategoryIsValid = true;
                                }

                            });


                        /*
                         * Jika subkategori sebelumnya berasal
                         * dari kategori lain, reset.
                         */
                        if (!selectedSubCategoryIsValid) {
                            subCategorySelect.value = '';
                        }
                    }


                    /*
                     * Jalankan saat kategori berubah.
                     */
                    if (categorySelect) {

                        categorySelect.addEventListener(
                            'change',
                            syncSubCategories
                        );

                    }


                    /*
                     * Sinkronkan state awal.
                     */
                    syncSubCategories();


                    /*
                     * Sebelum submit, jangan kirim subcategory
                     * jika belum dipilih.
                     */
                    form.addEventListener('submit', function () {

                        if (
                            subCategorySelect.disabled ||
                            subCategorySelect.value === ''
                        ) {
                            subCategorySelect.disabled = true;
                        }

                    });

                });

            });
        </script>
    @endpush
@endonce

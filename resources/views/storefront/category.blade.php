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
            <div class="d-flex align-items-center gap-2">

                <span class="small text-muted d-none d-sm-inline">
                    Urutkan:
                </span>

                <form
                    action="{{ url()->current() }}"
                    method="GET"
                    class="m-0"
                >

                    @foreach (request()->except('sort', 'page') as $key => $value)

                        @if (is_array($value))

                            @foreach ($value as $item)
                                <input
                                    type="hidden"
                                    name="{{ $key }}[]"
                                    value="{{ $item }}"
                                >
                            @endforeach

                        @else

                            <input
                                type="hidden"
                                name="{{ $key }}"
                                value="{{ $value }}"
                            >

                        @endif

                    @endforeach


                    <select
                        name="sort"
                        class="form-select form-select-sm"
                        onchange="this.form.submit()"
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

                </form>

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
                        {{ $products->onEachSide(1)->links('pagination::bootstrap-5') }}

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
</script>

@endpush

@extends('layouts.storefront')

@section('title', 'MeiStore - Toko Online')

@section('content')

    {{-- Hero --}}
    <section class="container py-4 py-lg-5">

        <div class="hero">

            <div class="hero-content p-4 p-md-5">

                <div class="row align-items-center">

                    <div class="col-lg-7">

                        <span class="badge bg-light text-primary mb-3">
                            Belanja Lebih Mudah
                        </span>

                        <h1 class="hero-title mb-3">
                            Temukan Produk
                            Favoritmu di MeiStore
                        </h1>

                        <p class="hero-text mb-4">
                            Jelajahi berbagai produk pilihan mulai dari
                            elektronik, fashion, rumah tangga, hingga
                            kebutuhan olahraga.
                        </p>

                        <a
                            href="{{ route('storefront.shop') }}"
                            class="btn btn-light btn-lg px-4"
                        >
                            Belanja Sekarang
                            <i class="bi bi-arrow-right ms-2"></i>
                        </a>

                    </div>

                    <div class="col-lg-5 d-none d-lg-block text-center">

                        <i
                            class="bi bi-bag-heart-fill"
                            style="font-size: 11rem; opacity: .9;"
                        ></i>

                    </div>

                </div>

            </div>

        </div>

    </section>


    {{-- Categories --}}
    <section class="container py-4">

        <div class="d-flex justify-content-between align-items-center mb-4">

            <div>
                <h2 class="section-title mb-1">
                    Kategori
                </h2>

                <p class="text-muted mb-0">
                    Temukan produk berdasarkan kategori
                </p>
            </div>

            <a
                href="{{ route('storefront.shop') }}"
                class="section-link"
            >
                Lihat Semua
                <i class="bi bi-arrow-right"></i>
            </a>

        </div>

        <div class="row g-3">

            @forelse ($categories as $category)

                <div class="col-6 col-lg-3">

                    <a
                        href="{{ route('storefront.category', $category->slug) }}"
                        class="d-block"
                    >

                        <div class="category-card">

                            @if ($category->image)
                                <img
                                    src="{{ $category->image }}"
                                    alt="{{ $category->name }}"
                                    loading="lazy"
                                >
                            @else
                                <div class="h-100 d-flex align-items-center justify-content-center bg-secondary">
                                    <i class="bi bi-grid text-white fs-1"></i>
                                </div>
                            @endif

                            <div class="category-overlay">

                                <div>
                                    <h3 class="category-name">
                                        {{ $category->name }}
                                    </h3>

                                    <div class="category-count">
                                        {{ $category->products_count }}
                                        produk
                                    </div>
                                </div>

                            </div>

                        </div>

                    </a>

                </div>

            @empty

                <div class="col-12">

                    <div class="alert alert-light border">
                        Belum ada kategori produk.
                    </div>

                </div>

            @endforelse

        </div>

    </section>


    {{-- Latest Products --}}
    <section class="container py-5">

        <div class="d-flex justify-content-between align-items-center mb-4">

            <div>
                <h2 class="section-title mb-1">
                    Produk Terbaru
                </h2>

                <p class="text-muted mb-0">
                    Produk terbaru yang tersedia di toko
                </p>
            </div>

            <a
                href="{{ route('storefront.shop') }}"
                class="section-link"
            >
                Lihat Semua
                <i class="bi bi-arrow-right"></i>
            </a>

        </div>


        <div class="row g-4">

            @forelse ($latestProducts as $product)

                <div class="col-6 col-md-4 col-lg-3">

                    <div class="product-card">

                        <a
                            href="{{ route('storefront.product', $product->slug) }}"
                        >

                            @if ($product->image)

                                <img
                                    src="{{ $product->image }}"
                                    alt="{{ $product->name }}"
                                    class="product-image"
                                    loading="lazy"
                                >

                            @else

                                <div
                                    class="product-image d-flex align-items-center justify-content-center"
                                >
                                    <i class="bi bi-image fs-1 text-secondary"></i>
                                </div>

                            @endif

                        </a>


                        <div class="product-body">

                            <div class="product-category">
                                {{ $product->category?->name }}
                            </div>

                            <a
                                href="{{ route('storefront.product', $product->slug) }}"
                            >
                                <h3 class="product-name">
                                    {{ $product->name }}
                                </h3>
                            </a>

                            <div class="product-price">
                                Rp {{ number_format($product->price, 0, ',', '.') }}
                            </div>

                            <div class="product-stock mt-1">

                                @if ($product->stock > 0)

                                    <i class="bi bi-check-circle text-success"></i>
                                    Stok {{ $product->stock }}

                                @else

                                    <i class="bi bi-x-circle text-danger"></i>
                                    Stok habis

                                @endif

                            </div>

                            <a
                                href="{{ route('storefront.product', $product->slug) }}"
                                class="btn btn-outline-primary w-100 mt-3"
                            >
                                Lihat Produk
                            </a>

                        </div>

                    </div>

                </div>

            @empty

                <div class="col-12">

                    <div class="text-center py-5">

                        <i class="bi bi-box-seam fs-1 text-muted"></i>

                        <h4 class="mt-3">
                            Belum ada produk
                        </h4>

                        <p class="text-muted">
                            Produk akan muncul di sini setelah
                            ditambahkan melalui admin.
                        </p>

                    </div>

                </div>

            @endforelse

        </div>

    </section>


    {{-- CTA --}}
    <section class="container pb-5">

        <div class="bg-light rounded-4 p-4 p-md-5 text-center">

            <i class="bi bi-stars fs-1 text-primary"></i>

            <h2 class="fw-bold mt-3">
                Siap menemukan produk favoritmu?
            </h2>

            <p class="text-muted">
                Jelajahi seluruh koleksi produk MeiStore.
            </p>

            <a
                href="{{ route('storefront.shop') }}"
                class="btn btn-primary px-4"
            >
                Jelajahi Produk
                <i class="bi bi-arrow-right ms-2"></i>
            </a>

        </div>

    </section>

@endsection

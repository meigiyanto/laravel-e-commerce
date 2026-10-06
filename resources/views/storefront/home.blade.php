@extends('layouts.storefront')

@section('title', 'MeiStore - Toko Online')

@section('content')
    {{-- =========================================================
         HERO
    ========================================================== --}}
    <section class="store-hero">
        <div class="container">
            <div class="store-hero-content">
                <span class="store-hero-label">
                    <i class="bi bi-stars me-2"></i>
                    Selamat datang di MeiStore
                </span>

                <h1 class="store-hero-title">Temukan Produk Favoritmu di Satu Tempat</h1>
                <p class="store-hero-text">
                    Jelajahi berbagai produk pilihan mulai dari
                    elektronik, fashion, kebutuhan rumah tangga,
                    hingga kebutuhan sehari-hari.
                </p>

                <div class="d-flex flex-wrap gap-2 mt-4">
                    <a
                        href="{{ route('storefront.shop') }}"
                        class="store-btn-primary"
                    >
                        Belanja Sekarang
                        <i class="bi bi-arrow-right"></i>
                    </a>

                    @if ($categories->isNotEmpty())
                        <a
                            href="#categories"
                            class="btn btn-outline-dark px-4 py-2"
                        >
                            Lihat Kategori
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </section>

    {{-- =========================================================
         CATEGORY SECTION
    ========================================================== --}}
    <section id="categories" class="store-section">
        <div class="container">
            <div class="store-section-header">
                <div>
                    <h2 class="store-section-title">
                        Belanja Berdasarkan Kategori
                    </h2>
                    <p class="store-section-subtitle">
                        Temukan produk sesuai kebutuhan Anda.
                    </p>
                </div>
                <a
                    href="{{ route('storefront.shop') }}"
                    class="store-section-link"
                >
                    Lihat Semua
                    <i class="bi bi-arrow-right ms-1"></i>
                </a>
            </div>

            @if ($categories->isNotEmpty())
                <div class="row g-3">
                    @foreach ($categories as $category)
                        <div class="col-6 col-md-4 col-lg-3">
                            <a
                                href="{{ route('storefront.category', $category->slug) }}"
                                class="d-block h-100"
                            >

                                <div class="store-category-card">
                                    @if ($category->image)

                                        <img
                                            src="{{ $category->image }}"
                                            alt="{{ $category->name }}"
                                            loading="lazy"
                                        >
                                    @else
                                        <div class="store-category-placeholder">
                                            <i class="bi bi-grid-3x3-gap fs-1"></i>
                                        </div>
                                    @endif

                                    <div class="store-category-overlay">
                                        <div>
                                            <h3 class="store-category-name">
                                                {{ $category->name }}
                                            </h3>
                                            <div class="store-category-count">
                                                {{ $category->products_count }}
                                                produk
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-5">
                    <div class="mb-3">
                        <i class="bi bi-grid fs-1 text-muted"></i>
                    </div>

                    <h4 class="fw-bold">
                        Belum Ada Kategori
                    </h4>

                    <p class="text-muted mb-0">
                        Kategori produk akan ditampilkan di sini.
                    </p>
                </div>
            @endif
        </div>
    </section>


    {{-- =========================================================
         LATEST PRODUCTS
    ========================================================== --}}
    <section class="store-section bg-light">
        <div class="container">
            <div class="store-section-header">
                <div>
                    <h2 class="store-section-title">Produk Terbaru</h2>
                    <p class="store-section-subtitle">Produk terbaru yang tersedia di MeiStore.</p>
                </div>

                <a
                    href="{{ route('storefront.shop') }}"
                    class="store-section-link"
                >
                    Lihat Semua
                    <i class="bi bi-arrow-right ms-1"></i>
                </a>
            </div>

            @if ($latestProducts->isNotEmpty())
                <div class="row g-3 g-md-4">
                    @foreach ($latestProducts as $product)
                        <div class="col-6 col-md-4 col-lg-3">
                            <x-store-product-card :product="$product" />
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-5">
                    <div class="mb-3">
                        <i class="bi bi-box-seam fs-1 text-muted"></i>
                    </div>
                    <h4 class="fw-bold">Belum Ada Produk</h4>
                    <p class="text-muted mb-0">Produk akan muncul di sini setelah ditambahkan melalui dashboard admin.</p>
                </div>
            @endif
        </div>
    </section>

    {{-- =========================================================
         SHOPPING PROMOTION
    ========================================================== --}}
    <section class="store-section">
        <div class="container">
            <div class="rounded-3 overflow-hidden" style=" background: linear-gradient(110deg, #fff7d6, #ffffff); border: 1px solid #f1e2a8;">
                <div class="row align-items-center g-0">
                    <div class="col-lg-8">
                        <div class="p-4 p-md-5">
                            <span class="badge mb-3" style="background: var(--store-primary); color: var(--store-dark);">
                                <i class="bi bi-lightning-charge-fill me-1"></i>
                                MeiStore
                            </span>

                            <h2 class="fw-bold mb-3">
                                Temukan lebih banyak produk
                                untuk kebutuhanmu.
                            </h2>

                            <p class="text-muted mb-4">
                                Jelajahi seluruh katalog MeiStore dan
                                temukan produk yang sesuai dengan kebutuhan
                                Anda.
                            </p>

                            <a
                                href="{{ route('storefront.shop') }}"
                                class="store-btn-primary"
                            >
                                Jelajahi Semua Produk
                                <i class="bi bi-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                    <div class="col-lg-4 d-none d-lg-block text-center">
                        <i
                            class="bi bi-bag-check-fill"
                            style="
                                font-size: 9rem;
                                color: var(--store-primary);
                            "
                        ></i>
                    </div>
                </div>
            </div>
        </div>
    </section>


    {{-- =========================================================
         TRUST / SHOPPING BENEFITS
    ========================================================== --}}
    <section class="pb-5">
        <div class="container">
            <div class="row g-3">
                <div class="col-6 col-lg-3">
                    <div
                        class="h-100 p-4 border rounded-3 bg-white"
                    >
                        <i
                            class="bi bi-box-seam fs-2"
                            style="color: var(--store-primary-dark);"
                        ></i>

                        <h5 class="fw-bold mt-3 mb-2">
                            Produk Pilihan
                        </h5>

                        <p class="text-muted small mb-0">
                            Produk dikelola melalui katalog
                            MeiStore.
                        </p>
                    </div>
                </div>

                <div class="col-6 col-lg-3">
                    <div class="h-100 p-4 border rounded-3 bg-white">
                        <i class="bi bi-cart-check fs-2" style="color: var(--store-primary-dark);"></i>

                        <h5 class="fw-bold mt-3 mb-2">
                            Belanja Mudah
                        </h5>

                        <p class="text-muted small mb-0">
                            Pilih produk, masukkan keranjang,
                            lalu checkout.
                        </p>
                    </div>
                </div>

                <div class="col-6 col-lg-3">
                    <div class="h-100 p-4 border rounded-3 bg-white">
                        <i class="bi bi-credit-card fs-2" style="color: var(--store-primary-dark);"></i>

                        <h5 class="fw-bold mt-3 mb-2">
                            Pembayaran
                        </h5>

                        <p class="text-muted small mb-0">
                            Dukungan proses pembayaran melalui
                            sistem yang tersedia.
                        </p>
                    </div>
                </div>

                <div class="col-6 col-lg-3">
                    <div
                        class="h-100 p-4 border rounded-3 bg-white"
                    >
                        <i class="bi bi-shield-check fs-2" style="color: var(--store-primary-dark);"></i>

                        <h5 class="fw-bold mt-3 mb-2">
                            Aman & Terpercaya
                        </h5>

                        <p class="text-muted small mb-0">
                            Data dan proses belanja dikelola
                            melalui aplikasi Laravel.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- =========================================================
         SERVICE FEATURES
    ========================================================== --}}
    <section class="store-service-strip">
        <div class="container">
            <div class="row g-0">

                <div class="col-12 col-md-6 col-lg-3">
                    <div class="store-service px-3">
                        <span class="store-service-icon">
                            <i class="bi bi-truck"></i>
                        </span>

                        <div>
                            <strong>Pengiriman Aman</strong>
                            <span>Pesanan dikirim dengan aman</span>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-md-6 col-lg-3">
                    <div class="store-service px-3">
                        <span class="store-service-icon">
                            <i class="bi bi-arrow-repeat"></i>
                        </span>

                        <div>
                            <strong>Pengembalian Mudah</strong>
                            <span>Proses refund dan retur tersedia</span>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-md-6 col-lg-3">
                    <div class="store-service px-3">
                        <span class="store-service-icon">
                            <i class="bi bi-shield-check"></i>
                        </span>

                        <div>
                            <strong>Pembayaran Aman</strong>
                            <span>Transaksi diproses secara aman</span>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-md-6 col-lg-3">
                    <div class="store-service px-3">
                        <span class="store-service-icon">
                            <i class="bi bi-headset"></i>
                        </span>

                        <div>
                            <strong>Customer Support</strong>
                            <span>Siap membantu kebutuhan Anda</span>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </section>
@endsection

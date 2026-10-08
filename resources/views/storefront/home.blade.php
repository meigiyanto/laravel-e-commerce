@extends('layouts.storefront')

@section('title', config('app.name') . ' - Online Store')

@section('content')

    {{-- =========================================================
         HERO
    ========================================================== --}}
    <section class="store-hero py-3 mb-3">
        <div class="container">
            <div class="store-hero-content">

                <span class="store-hero-label">
                    <i class="bi bi-stars me-2"></i>
                    Welcome to {{ config('app.name') }}
                </span>

                <h1 class="store-hero-title">
                    Find Your Favorite Products in One Place
                </h1>

                <p class="store-hero-text">
                    Explore a wide range of selected products, from
                    electronics, fashion, and household goods
                    to everyday essentials.
                </p>

                <div class="d-flex flex-wrap gap-2 mt-4">
                    <a
                        href="{{ route('storefront.shop') }}"
                        class="store-btn-primary"
                    >
                        Shop Now
                        <i class="bi bi-arrow-right"></i>
                    </a>

                    @if ($categories->isNotEmpty())
                        <a
                            href="#categories"
                            class="btn btn-outline-dark px-4 py-2"
                        >
                            View Categories
                        </a>
                    @endif
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
                    <div class="store-home-benefit">
                        <i
                            class="bi bi-box-seam store-home-benefit-icon"
                            aria-hidden="true"
                        ></i>

                        <h5 class="fw-bold mt-3 mb-2">
                            Featured Products
                        </h5>

                        <p class="text-muted small mb-0">
                           Products are managed via the {{ config('app.name') }} catalog.
                        </p>
                    </div>
                </div>


                <div class="col-6 col-lg-3">
                    <div class="store-home-benefit">
                        <i
                            class="bi bi-cart-check store-home-benefit-icon"
                            aria-hidden="true"
                        ></i>

                        <h5 class="fw-bold mt-3 mb-2">
                            Easy Shopping
                        </h5>

                        <p class="text-muted small mb-0">
                            Select a product, add it to your cart,
                            then proceed to checkout.
                        </p>
                    </div>
                </div>


                <div class="col-6 col-lg-3">
                    <div class="store-home-benefit">
                        <i
                            class="bi bi-credit-card store-home-benefit-icon"
                            aria-hidden="true"
                        ></i>

                        <h5 class="fw-bold mt-3 mb-2">
                            Payment
                        </h5>

                        <p class="text-muted small mb-0">
                            Support for payment processing via
                            available systems.
                        </p>
                    </div>
                </div>


                <div class="col-6 col-lg-3">
                    <div class="store-home-benefit">
                        <i
                            class="bi bi-shield-check store-home-benefit-icon"
                            aria-hidden="true"
                        ></i>

                        <h5 class="fw-bold mt-3 mb-2">
                            Safe & Trusted
                        </h5>

                        <p class="text-muted small mb-0">
                            Shopping data and processes are managed
                            via a Laravel application.
                        </p>
                    </div>
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
                        Shop by Category
                    </h2>

                    <p class="store-section-subtitle">
                        Find products that meet your needs.
                    </p>
                </div>

                <a
                    href="{{ route('storefront.shop') }}"
                    class="store-section-link"
                >
                    View All
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

                                    @if ($category->image_url)
                                        <img
                                            src="{{ $category->image_url }}"
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
                        No Category Yet
                    </h4>

                    <p class="text-muted mb-0">
                        Product categories will be displayed here.
                    </p>
                </div>

            @endif

        </div>
    </section>

    {{-- =========================================================
    SHOPPING PROMOTION
    ========================================================== --}}
    <section class="store-section">
        <div class="container">

            <div class="store-home-promotion">
                <div class="row align-items-center g-0">

                    <div class="col-lg-8">
                        <div class="p-4 p-md-5">

                            <span class="store-home-promotion-badge">
                                <i class="bi bi-lightning-charge-fill me-1"></i>
                                {{ config('app.name') }}
                            </span>

                            <h2 class="fw-bold mb-3">
                                Discover more products
                                for your needs.
                            </h2>

                            <p class="text-muted mb-4">
                                Explore the entire {{ config('app.name') }} catalog and
                                find products that meet your needs.
                            </p>

                            <a
                                href="{{ route('storefront.shop') }}"
                                class="store-btn-primary"
                            >
                                Explore All Products
                                <i class="bi bi-arrow-right"></i>
                            </a>

                        </div>
                    </div>

                    <div class="col-lg-4 d-none d-lg-block text-center">
                        <i class="bi bi-bag-check-fill store-home-promotion-icon" aria-hidden="true"></i>
                    </div>

                </div>
            </div>

        </div>
    </section>

    {{-- =========================================================
         LATEST PRODUCTS
    ========================================================== --}}
    <section class="store-section bg-light">
        <div class="container">

            <div class="store-section-header">
                <div>
                    <h2 class="store-section-title">
                        Newest Products
                    </h2>

                    <p class="store-section-subtitle">
                        The latest products available at {{ config('app.name') }}.
                    </p>
                </div>

                <a
                    href="{{ route('storefront.shop') }}"
                    class="store-section-link"
                >
                    View All
                    <i class="bi bi-arrow-right ms-1"></i>
                </a>
            </div>


            @if ($latestProducts->isNotEmpty())
                <div class="row g-3 g-md-4">
                    @foreach ($latestProducts as $product)
                        <div class="col-6 col-md-6 col-lg-3">
                            <x-store-product-card :product="$product" />
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-5">
                    <div class="mb-3">
                        <i class="bi bi-box-seam fs-1 text-muted"></i>
                    </div>

                    <h4 class="fw-bold">
                        No products yet
                    </h4>

                    <p class="text-muted mb-0">
                        Products will appear here after being added
                        via the admin dashboard.
                    </p>
                </div>
            @endif

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
                            <strong>Secure Shipping</strong>
                            <span>The order is shipped securely.</span>
                        </div>
                    </div>
                </div>


                <div class="col-12 col-md-6 col-lg-3">
                    <div class="store-service px-3">
                        <span class="store-service-icon">
                            <i class="bi bi-arrow-repeat"></i>
                        </span>

                        <div>
                            <strong>Easy Returns</strong>
                            <span>Refund and return processes are available.</span>
                        </div>
                    </div>
                </div>


                <div class="col-12 col-md-6 col-lg-3">
                    <div class="store-service px-3">
                        <span class="store-service-icon">
                            <i class="bi bi-shield-check"></i>
                        </span>

                        <div>
                            <strong>Secure Payment</strong>
                            <span>Transactions are processed securely.</span>
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
                            <span>Ready to assist with your needs.</span>
                        </div>
                    </div>
                </div>

            </div>

        </div>
    </section>

@endsection

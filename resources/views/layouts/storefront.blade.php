<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    <meta
        name="csrf-token"
        content="{{ csrf_token() }}"
    >

    <title>
        @yield('title', 'MeiStore')
    </title>

    {{-- Bootstrap 5 --}}
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    {{-- Bootstrap Icons --}}
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css"
        rel="stylesheet"
    >

    <style>
        :root {
            --store-primary: #0d6efd;
            --store-dark: #111827;
            --store-light: #f8f9fa;
            --store-border: #e5e7eb;
        }

        body {
            background: #fff;
            color: #212529;
            font-family:
                system-ui,
                -apple-system,
                BlinkMacSystemFont,
                "Segoe UI",
                sans-serif;
        }

        a {
            text-decoration: none;
        }

        .store-navbar {
            background: #fff;
            border-bottom: 1px solid var(--store-border);
        }

        .store-brand {
            font-size: 1.35rem;
            font-weight: 800;
            color: var(--store-dark);
        }

        .store-brand span {
            color: var(--store-primary);
        }

        .navbar-search {
            max-width: 520px;
            width: 100%;
        }

        .navbar-search .form-control {
            border-right: 0;
        }

        .navbar-search .btn {
            border-left: 0;
        }

        .hero {
            background:
                linear-gradient(
                    135deg,
                    #0d6efd 0%,
                    #084298 100%
                );
            color: #fff;
            border-radius: 1.25rem;
            overflow: hidden;
            position: relative;
        }

        .hero::after {
            content: "";
            position: absolute;
            width: 280px;
            height: 280px;
            right: -100px;
            top: -100px;
            background: rgba(255, 255, 255, .08);
            border-radius: 50%;
        }

        .hero-content {
            position: relative;
            z-index: 1;
        }

        .hero-title {
            font-size: clamp(2rem, 5vw, 3.5rem);
            font-weight: 800;
            line-height: 1.1;
        }

        .hero-text {
            color: rgba(255, 255, 255, .85);
            max-width: 580px;
        }

        .section-title {
            font-weight: 800;
            color: var(--store-dark);
        }

        .section-link {
            color: var(--store-primary);
            font-weight: 600;
        }

        .category-card {
            position: relative;
            height: 190px;
            overflow: hidden;
            border-radius: 1rem;
            background: #f1f3f5;
        }

        .category-card img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform .3s ease;
        }

        .category-card:hover img {
            transform: scale(1.06);
        }

        .category-overlay {
            position: absolute;
            inset: 0;
            display: flex;
            align-items: end;
            padding: 1.25rem;
            background:
                linear-gradient(
                    to top,
                    rgba(0, 0, 0, .72),
                    rgba(0, 0, 0, 0)
                );
        }

        .category-name {
            color: #fff;
            font-size: 1.15rem;
            font-weight: 700;
            margin: 0;
        }

        .category-count {
            color: rgba(255, 255, 255, .8);
            font-size: .85rem;
        }

        .product-card {
            height: 100%;
            border: 1px solid var(--store-border);
            border-radius: 1rem;
            overflow: hidden;
            background: #fff;
            transition:
                transform .2s ease,
                box-shadow .2s ease;
        }

        .product-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 .75rem 1.5rem rgba(0, 0, 0, .08);
        }

        .product-image {
            height: 230px;
            width: 100%;
            object-fit: cover;
            background: #f1f3f5;
        }

        .product-body {
            padding: 1rem;
        }

        .product-category {
            color: #6c757d;
            font-size: .8rem;
            font-weight: 600;
        }

        .product-name {
            color: var(--store-dark);
            font-size: 1rem;
            font-weight: 700;
            margin-top: .35rem;
            margin-bottom: .5rem;
        }

        .product-price {
            color: var(--store-primary);
            font-size: 1.1rem;
            font-weight: 800;
        }

        .product-stock {
            font-size: .8rem;
            color: #6c757d;
        }

        .store-footer {
            background: var(--store-dark);
            color: rgba(255, 255, 255, .75);
        }

        .store-footer h5,
        .store-footer h6 {
            color: #fff;
        }

        .store-footer a {
            color: rgba(255, 255, 255, .7);
        }

        .store-footer a:hover {
            color: #fff;
        }

        @media (max-width: 767.98px) {
            .hero {
                border-radius: 0;
            }

            .product-image {
                height: 200px;
            }

            .category-card {
                height: 160px;
            }

            .navbar-search {
                margin-top: 1rem;
            }
        }
    </style>

    @stack('styles')
</head>

<body>

    {{-- Navbar --}}
    <nav class="navbar navbar-expand-lg store-navbar sticky-top">
        <div class="container py-2">

            <a
                class="navbar-brand store-brand"
                href="{{ route('storefront.home') }}"
            >
                Mei<span>Store</span>
            </a>

            <button
                class="navbar-toggler"
                type="button"
                data-bs-toggle="collapse"
                data-bs-target="#storeNavbar"
                aria-controls="storeNavbar"
                aria-expanded="false"
                aria-label="Toggle navigation"
            >
                <span class="navbar-toggler-icon"></span>
            </button>

            <div
                class="collapse navbar-collapse"
                id="storeNavbar"
            >

                <form
                    class="navbar-search mx-lg-4 my-2 my-lg-0"
                    action="{{ route('storefront.shop') }}"
                    method="GET"
                >
                    <div class="input-group">

                        <input
                            type="search"
                            name="q"
                            class="form-control"
                            placeholder="Cari produk..."
                            value="{{ request('q') }}"
                        >

                        <button
                            class="btn btn-primary"
                            type="submit"
                        >
                            <i class="bi bi-search"></i>
                        </button>

                    </div>
                </form>

                <ul class="navbar-nav ms-auto align-items-lg-center">
                    <li class="nav-item">
                        <a
                            class="nav-link"
                            href="{{ route('storefront.home') }}"
                        >
                            Home
                        </a>
                    </li>
                @auth
                    <li class="nav-item dropdown">
                        <a
                            class="nav-link dropdown-toggle"
                            href="#"
                            role="button"
                            data-bs-toggle="dropdown"
                            aria-expanded="false"
                        >

                            <i class="bi bi-person-circle me-1"></i>
                            Account
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <a
                                    class="dropdown-item"
                                    href="{{ route('dashboard') }}"
                                >
                                    <i class="bi bi-speedometer2 me-2"></i>
                                    Dashboard

                                </a>
                            </li>
                            <li>
                                <a
                                    class="dropdown-item"
                                    href="{{ route('orders.index') }}"
                                >
                                    <i class="bi bi-bag me-2"></i>
                                    My Orders
                                </a>
                            </li>
                            <li>
                                <a
                                    class="dropdown-item"
                                    href="{{ route('profile.edit') }}"
                                >
                                    <i class="bi bi-person me-2"></i>
                                    Profile
                                </a>
                            </li>
                        </ul>
                    </li>
                @else
                    <li class="nav-item">
                        <a
                            class="nav-link"
                            href="{{ route('login') }}"
                        >
                            <i class="bi bi-person me-1"></i>
                            Login
                        </a>
                    </li>
                @endauth

                {{-- Cart akan disambungkan pada tahap berikutnya --}}
                <li class="nav-item ms-lg-2">
                    <span class="nav-link text-muted">
                        <i class="bi bi-cart3"></i>
                        Cart
                    </span>
                </li>

                @auth
                    <li class="nav-item ms-lg-2">
                        <a
                            class="nav-link position-relative"
                            href="{{ route('cart.index') }}"
                        >
                            <i class="bi bi-cart3"></i>
                            Cart
                        </a>
                    </li>
                @else
                    <li class="nav-item ms-lg-2">
                        <a
                            class="nav-link"
                            href="{{ route('login') }}"
                        >
                            <i class="bi bi-cart3"></i>
                            Cart
                        </a>
                    </li>
                @endauth

                </ul>

            </div>
        </div>
    </nav>


    {{-- Main --}}
    <main>
        @yield('content')
    </main>


    {{-- Footer --}}
    <footer class="store-footer mt-5">
        <div class="container py-5">

            <div class="row g-4">

                <div class="col-lg-5">
                    <h5 class="fw-bold">
                        MeiStore
                    </h5>

                    <p class="mb-0">
                        Toko online sederhana untuk berbagai
                        kebutuhan elektronik, fashion, rumah tangga,
                        olahraga, dan lainnya.
                    </p>
                </div>

                <div class="col-6 col-lg-2">
                    <h6 class="fw-bold">
                        Store
                    </h6>

                    <ul class="list-unstyled mb-0">
                        <li class="mb-2">
                            <a href="{{ route('storefront.home') }}">
                                Home
                            </a>
                        </li>

                        <li class="mb-2">
                            <a href="{{ route('storefront.shop') }}">
                                Shop
                            </a>
                        </li>
                    </ul>
                </div>

                <div class="col-6 col-lg-2">
                    <h6 class="fw-bold">
                        Account
                    </h6>

                    <ul class="list-unstyled mb-0">
                        @auth
                            <li class="mb-2">
                                <a href="{{ route('dashboard') }}">
                                    Dashboard
                                </a>
                            </li>
                        @else
                            <li class="mb-2">
                                <a href="{{ route('login') }}">
                                    Login
                                </a>
                            </li>

                            <li class="mb-2">
                                <a href="{{ route('register') }}">
                                    Register
                                </a>
                            </li>
                        @endauth
                    </ul>
                </div>

                <div class="col-lg-3">
                    <h6 class="fw-bold">
                        Follow Us
                    </h6>

                    <div class="d-flex gap-3 fs-4">
                        <a href="#">
                            <i class="bi bi-instagram"></i>
                        </a>

                        <a href="#">
                            <i class="bi bi-facebook"></i>
                        </a>

                        <a href="#">
                            <i class="bi bi-twitter-x"></i>
                        </a>
                    </div>
                </div>

            </div>

            <hr class="border-secondary my-4">

            <div class="text-center small">
                &copy; {{ date('Y') }} MeiStore.
                All rights reserved.
            </div>

        </div>
    </footer>


    <script
        src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js">
    </script>

    @stack('scripts')

</body>

</html>

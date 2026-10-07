
    {{-- =========================================================
         FOOTER
    ========================================================== --}}
    <footer class="store-footer">
        <div class="store-footer-main">
            <div class="container">
                <div class="row g-4">
                    {{-- Brand --}}
                    <div class="col-lg-4">
                        <a
                            href="{{ route('storefront.home') }}"
                            class="store-footer-brand"
                        >
                            Mei<span>Store</span>
                        </a>

                        <p class="mt-3 mb-4">
                            MeiStore is a Laravel-based e-commerce project that provides a modern, simple, and responsive online shopping experience.
                        </p>

                        <div class="d-flex gap-3 fs-5">

                            <a
                                href="{{ route('dashboard') }}"
                                aria-label="Instagram"
                            >
                                <i class="bi bi-instagram"></i>
                            </a>

                            <a
                                href="#"
                                aria-label="Facebook"
                            >
                                <i class="bi bi-facebook"></i>
                            </a>

                            <a
                                href="#"
                                aria-label="Twitter"
                            >
                                <i class="bi bi-twitter-x"></i>
                            </a>

                            <a
                                href="#"
                                aria-label="YouTube"
                            >
                                <i class="bi bi-youtube"></i>
                            </a>

                        </div>

                    </div>


                    {{-- Store --}}
                    <div class="col-6 col-lg-2">

                        <h6 class="store-footer-title">
                            Store
                        </h6>

                        <ul>
                            <li>
                                <a href="{{ route('storefront.home') }}">
                                    Home
                                </a>
                            </li>

                            <li>
                                <a href="{{ route('storefront.shop') }}">
                                    All Product
                                </a>
                            </li>

                            @auth
                                <li>
                                    <a href="{{ route('wishlist.index') }}">
                                        Wishlist
                                    </a>
                                </li>

                                <li>
                                    <a href="{{ route('compare.index') }}">
                                        Compare
                                    </a>
                                </li>
                            @endauth
                        </ul>
                    </div>

                    {{-- Customer --}}
                    <div class="col-6 col-lg-2">
                        <h6 class="store-footer-title">Customer</h6>
                        <ul>
                            @auth
                                <li>
                                    <a href="#">
                                        Dashboard
                                    </a>
                                </li>

                                <li>
                                    <a href="{{ route('orders.index') }}">
                                        My Order
                                    </a>
                                </li>

                                <li>
                                    <a href="{{ route('profile.edit') }}">
                                        Profile
                                    </a>
                                </li>
                            @else
                                <li>
                                    <a href="{{ route('login') }}">
                                        Login
                                    </a>
                                </li>

                                <li>
                                    <a href="{{ route('register') }}">
                                        Register
                                    </a>
                                </li>
                            @endauth

                        </ul>

                    </div>


                    {{-- Contact --}}
                    <div class="col-lg-4">

                        <h6 class="store-footer-title">
                            Hubungi Kami
                        </h6>

                        <ul>

                            <li>
                                <i class="bi bi-headset me-2"></i>
                                Customer Support
                            </li>

                            <li>
                                <i class="bi bi-envelope me-2"></i>
                                support@meistore.test
                            </li>

                            <li>
                                <i class="bi bi-clock me-2"></i>
                                Senin - Minggu, 08:00 - 22:00
                            </li>

                        </ul>

                    </div>

                </div>

            </div>
        </div>


        {{-- Footer bottom --}}
        <div class="store-footer-bottom">
            <div class="container">

                <div class="row align-items-center g-3">

                    <div class="col-md-6">
                        &copy; {{ date('Y') }} MeiStore.
                        All rights reserved.
                    </div>

                    <div class="col-md-6">

                        <div class="store-payment-icons justify-content-md-end">

                            <span class="store-payment-icon">
                                <i class="bi bi-credit-card"></i>
                            </span>

                            <span class="store-payment-icon">
                                <i class="bi bi-wallet2"></i>
                            </span>

                            <span class="store-payment-icon">
                                <i class="bi bi-bank"></i>
                            </span>

                            <span class="store-payment-icon">
                                <i class="bi bi-shield-check"></i>
                            </span>

                        </div>

                    </div>

                </div>

            </div>
        </div>

    </footer>

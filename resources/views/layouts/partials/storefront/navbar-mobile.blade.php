{{-- =========================================================
        MOBILE NAVIGATION
========================================================== --}}
<nav class="store-mobile-nav d-lg-none">
    <div class="container">
        <div class="store-mobile-nav-bar">

            {{-- Menu Toggle --}}
            <button
                type="button"
                class="store-mobile-menu-toggle"
                aria-expanded="false"
                aria-controls="storeMobileMenu"
                aria-label="Buka menu navigasi"
            >
                <span class="store-mobile-menu-icon">
                    <i class="bi bi-list store-mobile-menu-open-icon"></i>
                    <i class="bi bi-x-lg store-mobile-menu-close-icon"></i>
                </span>
                <span class="store-mobile-menu-label">Menu</span>
            </button>

            {{-- Brand --}}
            <a
                href="{{ route('storefront.home') }}"
                class="store-mobile-brand"
            >
                MeiStore
            </a>

            {{-- Cart --}}
            <a
                href="{{ route('cart.index') }}"
                class="store-mobile-cart"
                aria-label="Keranjang"
            >
                <i class="bi bi-cart3"></i>

                @php
                    $cartCount = app(\App\Services\CartService::class)->count();
                @endphp

                @if ($cartCount > 0)
                    <span class="store-mobile-cart-badge">
                        {{ $cartCount > 99 ? '99+' : $cartCount }}
                    </span>
                @endif
            </a>

        </div>

        {{-- =================================================
                MOBILE MENU PANEL
        ================================================== --}}
        <div
            id="storeMobileMenu"
            class="store-mobile-menu"
            aria-hidden="true"
        >
            <div class="store-mobile-menu-inner">

                {{-- Primary Navigation --}}
                <div class="store-mobile-menu-section">

                    <a
                        href="{{ route('storefront.home') }}"
                        class="store-mobile-menu-link {{ request()->routeIs('storefront.home') ? 'active' : '' }}"
                        @if(request()->routeIs('storefront.home'))
                            aria-current="page"
                        @endif
                    >
                        <span class="store-mobile-menu-link-icon">
                            <i class="bi bi-house"></i>
                        </span>

                        <span>Home</span>
                    </a>

                    <a
                        href="{{ route('storefront.shop') }}"
                        class="store-mobile-menu-link {{ request()->routeIs('storefront.shop', 'storefront.category', 'storefront.product') ? 'active' : '' }}"
                        @if(request()->routeIs('storefront.shop', 'storefront.category', 'storefront.product'))
                            aria-current="page"
                        @endif
                    >
                        <span class="store-mobile-menu-link-icon">
                            <i class="bi bi-shop"></i>
                        </span>

                        <span>Shop</span>
                    </a>

                </div>


                {{-- Department --}}
                <div class="store-mobile-menu-section">

                    <button
                        type="button"
                        class="store-mobile-department-toggle"
                        aria-expanded="false"
                        aria-controls="storeMobileDepartments"
                    >
                        <span class="store-mobile-menu-link-content">
                            <span class="store-mobile-menu-link-icon">
                                <i class="bi bi-grid-3x3-gap"></i>
                            </span>

                            <span>Shop by Department</span>
                        </span>

                        <i class="bi bi-chevron-down store-mobile-department-chevron"></i>
                    </button>

                    <div
                        id="storeMobileDepartments"
                        class="store-mobile-departments"
                        aria-hidden="true"
                    >
                        @forelse ($storefrontCategories ?? collect() as $category)

                            <a
                                href="{{ route('storefront.category', $category->slug) }}"
                                class="store-mobile-department-item"
                            >
                                <span>
                                    {{ $category->name }}
                                </span>

                                <i class="bi bi-chevron-right"></i>
                            </a>

                        @empty

                            <div class="store-mobile-department-empty">
                                Belum ada kategori.
                            </div>

                        @endforelse

                        @if (($storefrontCategories ?? collect())->isNotEmpty())

                            <a
                                href="{{ route('storefront.shop') }}"
                                class="store-mobile-department-all"
                            >
                                <span>Lihat Semua Produk</span>
                                <i class="bi bi-arrow-right"></i>
                            </a>

                        @endif
                    </div>

                </div>


                {{-- Customer Navigation --}}
                @auth
                    <div class="store-mobile-menu-section">

                        <div class="store-mobile-menu-heading">
                            Akun Saya
                        </div>

                        <a
                            href="{{ route('wishlist.index') }}"
                            class="store-mobile-menu-link {{ request()->routeIs('wishlist.index') ? 'active' : '' }}"
                            @if(request()->routeIs('wishlist.index'))
                                aria-current="page"
                            @endif
                        >
                            <span class="store-mobile-menu-link-icon">
                                <i class="bi bi-heart"></i>
                            </span>

                            <span>Wishlist</span>
                        </a>

                        <a
                            href="{{ route('compare.index') }}"
                            class="store-mobile-menu-link {{ request()->routeIs('compare.index') ? 'active' : '' }}"
                            @if(request()->routeIs('compare.index'))
                                aria-current="page"
                            @endif
                        >
                            <span class="store-mobile-menu-link-icon">
                                <i class="bi bi-bar-chart"></i>
                            </span>

                            <span>Compare</span>
                        </a>

                        <a
                            href="{{ route('orders.index') }}"
                            class="store-mobile-menu-link {{ request()->routeIs('orders.index') ? 'active' : '' }}"
                            @if(request()->routeIs('orders.index'))
                                aria-current="page"
                            @endif
                        >
                            <span class="store-mobile-menu-link-icon">
                                <i class="bi bi-receipt"></i>
                            </span>

                            <span>Order</span>
                        </a>

                    </div>
                @endauth


                {{-- Bottom Action --}}
                <div class="store-mobile-menu-footer">

                    <a
                        href="{{ route('cart.index') }}"
                        class="store-mobile-cart-link"
                    >
                        <span>
                            <i class="bi bi-cart3"></i>
                            Keranjang
                        </span>

                        @if ($cartCount > 0)
                            <span class="store-mobile-cart-count">
                                {{ $cartCount }}
                            </span>
                        @endif
                    </a>

                    @guest
                        <a
                            href="{{ route('login') }}"
                            class="store-mobile-login-link"
                        >
                            <span>
                                <i class="bi bi-person"></i>
                                Login
                            </span>

                            <i class="bi bi-arrow-right"></i>
                        </a>
                    @endguest

                </div>

            </div>
        </div>
    </div>
</nav>
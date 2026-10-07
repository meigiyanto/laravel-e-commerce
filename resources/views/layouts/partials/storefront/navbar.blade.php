{{-- =========================================================
        DESKTOP NAVIGATION
========================================================== --}}
<nav class="store-navigation d-none d-lg-block">
    <div class="container">
        <div class="store-navigation-inner d-flex align-items-center">
            {{-- SHOP BY DEPARTMENT --}}
            <div class="store-department store-department-dropdown">

                <button
                    type="button"
                    class="store-department-toggle"
                    aria-expanded="false"
                    aria-controls="storeDepartmentMenu"
                >
                    <i class="bi bi-grid-3x3-gap-fill"></i>
                    <span>Shop by Department</span>
                    <i class="bi bi-chevron-down store-department-chevron"></i>
                </button>

                <div
                    id="storeDepartmentMenu"
                    class="store-department-menu"
                    aria-hidden="true"
                >
                    <div class="store-department-menu-header">
                        <span>Shop by Department</span>
                        <span class="store-department-menu-count">
                            {{ ($storefrontCategories ?? collect())->count() }} kategori
                        </span>
                    </div>

                    <div class="store-department-menu-grid">

                        @forelse ($storefrontCategories ?? collect() as $category)

                            <a
                                href="{{ route('storefront.category', $category->slug) }}"
                                class="store-department-item"
                            >
                                <span class="store-department-item-icon">
                                    <i class="bi bi-grid"></i>
                                </span>

                                <span class="store-department-item-name">
                                    {{ $category->name }}
                                </span>

                                <i class="bi bi-chevron-right store-department-item-arrow"></i>
                            </a>

                        @empty

                            <div class="store-department-empty">
                                Belum ada kategori.
                            </div>

                        @endforelse

                    </div>

                    <a
                        href="{{ route('storefront.shop') }}"
                        class="store-department-all"
                    >
                        <span>
                            <i class="bi bi-shop me-2"></i>
                            Lihat Semua Produk
                        </span>

                        <i class="bi bi-arrow-right"></i>
                    </a>
                </div>

            </div>

            {{-- HOME --}}
            <a
                href="{{ route('storefront.home') }}"
                class="store-nav-link {{ request()->routeIs('storefront.home') ? 'active' : '' }}"
                @if(request()->routeIs('storefront.home'))
                    aria-current="page"
                @endif
            >
                Home
            </a>

            {{-- SHOP --}}
            <a
                href="{{ route('storefront.shop') }}"
                class="store-nav-link {{ request()->routeIs('storefront.shop', 'storefront.category', 'storefront.product') ? 'active' : '' }}"
                @if(request()->routeIs('storefront.shop', 'storefront.category', 'storefront.product'))
                    aria-current="page"
                @endif
            >
                Shop
            </a>

            {{-- AUTHENTICATED CUSTOMER LINKS --}}
            @auth

                <a
                    href="{{ route('wishlist.index') }}"
                    class="store-nav-link {{ request()->routeIs('wishlist.index') ? 'active' : '' }}"
                    @if(request()->routeIs('wishlist.index'))
                        aria-current="page"
                    @endif
                >
                    Wishlist
                </a>

                <a
                    href="{{ route('compare.index') }}"
                    class="store-nav-link {{ request()->routeIs('compare.index') ? 'active' : '' }}"
                    @if(request()->routeIs('compare.index'))
                        aria-current="page"
                    @endif
                >
                    Compare
                </a>

                <a
                    href="{{ route('orders.index') }}"
                    class="store-nav-link {{ request()->routeIs('orders.index') ? 'active' : '' }}"
                    @if(request()->routeIs('orders.index'))
                        aria-current="page"
                    @endif
                >
                    Order
                </a>

            @endauth

            {{-- PROMO --}}
            <div class="ms-auto d-flex align-items-center">
                <a
                    href="{{ route('storefront.shop') }}"
                    class="store-nav-link"
                >
                    <i class="bi bi-stars me-2"></i>
                    Promo
                </a>
            </div>

        </div>
    </div>
</nav>
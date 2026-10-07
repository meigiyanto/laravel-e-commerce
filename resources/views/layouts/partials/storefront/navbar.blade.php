{{-- =========================================================
        DESKTOP NAVIGATION
========================================================== --}}
<nav class="store-navigation d-none d-lg-block">
    <div class="container">
        <div class="store-navigation-inner d-flex align-items-center">
            <a href="{{ route('storefront.shop') }}" class="store-department">
                <i class="bi bi-grid-3x3-gap-fill"></i>
                Shop by Department
                <i class="bi bi-chevron-down ms-2"></i>
            </a>
            <a href="{{ route('storefront.home') }}" class="store-nav-link {{ request()->routeIs('storefront.home') ? 'active' : '' }}">Home</a>
            <a href="{{ route('storefront.shop') }}" class="store-nav-link {{ request()->routeIs('storefront.shop', 'storefront.category', 'storefront.product') ? 'active' : '' }}">Shop</a>
            @auth
                <a href="{{ route('wishlist.index') }}" class="store-nav-link {{ request()->routeIs('wishlist') ? 'active' : '' }}">Wishlist</a>
                <a href="{{ route('compare.index') }}" class="store-nav-link {{ request()->routeIs('compare.index') ? 'active' : '' }}">Compare</a>
                <a href="{{ route('orders.index') }}" class="store-nav-link {{ request()->routeIs('orders.index') ? 'active' : '' }}">Order</a>
            @endauth
            <div class="ms-auto d-flex align-items-center">
                <a href="{{ route('storefront.shop') }}" class="store-nav-link"><i class="bi bi-stars me-2"></i>Promo</a>
            </div>
        </div>
    </div>
</nav>

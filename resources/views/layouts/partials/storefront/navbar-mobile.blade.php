{{-- =========================================================
        MOBILE QUICK NAV
========================================================== --}}
<nav class="d-lg-none navbar navbar-expand-lg bg-body-tertiary">
    <div class="container">
        <a class="navbar-brand" href="#">Menu</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('storefront.home') ? 'active' : '' }}" @if(request()->routeIs('storefront.home')) ? 'aria-current="page"' : '' @endif href="{{ route('storefront.home') }}">Home</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('storefront.shop') ? 'active' : '' }}" @if(request()->routeIs('storefront.shop')) ? 'aria-current="page"' : '' @endif href="{{ route('storefront.shop') }}">Shop</a>
                </li>
                @auth
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('wishlist.index ') ? 'active' : '' }}" @if(request()->routeIs('wishlist.index')) ? 'aria-current="page"' : '' @endif href="{{ route('wishlist.index') }}">Wishlist</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('compare.index') ? 'active' : '' }}" @if(request()->routeIs('compare.index')) ? 'aria-current="page"' : '' @endif href="{{ route('wishlist.index') }}">Compare</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('orders.index') ? 'active' : '' }}" @if(request()->routeIs('orders.index')) ? 'aria-current="page"' : '' @endif href="{{ route('orders.index') }}">Order</a>
                    </li>
                @endauth
            </ul>
        </div>
    </div>
</nav>

{{-- =========================================================
        MOBILE QUICK NAV
========================================================== --}}
<nav class="d-lg-none navbar navbar-expand-lg bg-body-tertiary">
    <div class="container">
        <a class="navbar-brand" href="#">Navbar</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link active" aria-current="page" href="#">Home</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#">Shop</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#">Wishlist</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#">Compare</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#">Order</a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<div class="container accordion accordion-flush" id="accordionNavbarMobile">
    <div class="accordion-item">
        <div>
            <h2 class="accordion-header">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#flush-navbarMobile" aria-expanded="false" aria-controls="flush-navbarMobile">
                Menu
                </button>
            </h2>
        </div>
        <div id="flush-navbarMobile" class="accordion-collapse collapse" data-bs-parent="#accordionNavbarMobile">
            <div class="container py-2">
                <div class="d-flex gap-2 overflow-auto">
                    <ul class="list-unstyled">
                        <li>
                            <a href="{{ route('storefront.home') }}" class="nav-link-item {{ request()->routeIs('storefront.home') ? 'active' : '' }}">Home</a>
                        </li>
                        <li>
                            <a href="{{ route('storefront.shop') }}" class="nav-link-item {{ request()->routeIs('storefront.shop') || request()->routeIs('storefront.category') ? 'active' : '' }}">Shop</a>
                        </li>
                        @auth
                            <li>
                                <a href="{{ route('wishlist.index') }}" class="nav-link-item {{ request()->routeIs('wishlist.index') ? 'active' : '' }}">Wishlist</a>
                            </li>
                            <li>
                                <a href="{{ route('compare.index') }}" class="nav-link-item {{ request()->routeIs('compare.index') ? 'active' : '' }}">Compare</a>
                            </li>
                            <li>
                                <a href="{{ route('orders.index') }}" class="nav-link-item {{ request()->routeIs('orders.index') ? 'active' : '' }}">Orders</a>
                            </li>
                        @endauth
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
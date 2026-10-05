    {{-- =========================================================
         MOBILE QUICK NAV
    ========================================================== --}}
    <div class="d-lg-none border-bottom bg-white">
        <div class="container py-2">

            <div class="d-flex gap-2 overflow-auto">

                <a
                    href="{{ route('storefront.home') }}"
                    class="btn btn-sm {{ request()->routeIs('storefront.home') ? 'btn-dark' : 'btn-light border' }}"
                >
                    Home
                </a>

                <a
                    href="{{ route('storefront.shop') }}"
                    class="btn btn-sm {{ request()->routeIs('storefront.shop') || request()->routeIs('storefront.category') ? 'btn-dark' : 'btn-light border' }}"
                >
                    Shop
                </a>

                @auth
                    <a
                        href="{{ route('wishlist.index') }}"
                        class="btn btn-sm btn-light border"
                    >
                        Wishlist
                    </a>

                    <a
                        href="{{ route('compare.index') }}"
                        class="btn btn-sm btn-light border"
                    >
                        Compare
                    </a>

                    <a
                        href="{{ route('orders.index') }}"
                        class="btn btn-sm btn-light border"
                    >
                        Pesanan
                    </a>
                @endauth

            </div>

        </div>
    </div>

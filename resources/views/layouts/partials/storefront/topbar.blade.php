    {{-- =========================================================
         TOP BAR
    ========================================================== --}}
    <div class="store-topbar">
        <div class="container">
            <div class="store-topbar-inner d-flex justify-content-between align-items-center">

                <div>
                    <i class="bi bi-truck me-1"></i>
                    Gratis pengiriman untuk pesanan tertentu
                </div>

                <div class="d-flex align-items-center gap-3">
                    <a href="{{ route('storefront.shop') }}">
                        Belanja
                    </a>

                    @auth
                        <a href="{{ route('orders.index') }}">
                            Lacak Pesanan
                        </a>
                    @else
                        <a href="{{ route('login') }}">
                            Login
                        </a>
                    @endauth
                </div>

            </div>
        </div>
    </div>

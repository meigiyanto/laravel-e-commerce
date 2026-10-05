
    {{-- =========================================================
         MOBILE BOTTOM NAV
    ========================================================== --}}
    <nav class="store-mobile-bottom">
        <a
            href="{{ route('storefront.home') }}"
            class="{{ request()->routeIs('storefront.home') ? 'active' : '' }}"
        >
            <i class="bi bi-house"></i>
            <span>Home</span>
        </a>

        <a
            href="{{ route('storefront.shop') }}"
            class="{{ request()->routeIs('storefront.shop', 'storefront.category', 'storefront.product') ? 'active' : '' }}"
        >
            <i class="bi bi-grid"></i>
            <span>Shop</span>
        </a>

        @auth

            @php
                $mobileCartCount = auth()->user()->cart?->items()->sum('quantity') ?? 0;
            @endphp

            <a href="{{ route('cart.index') }}">
                <i class="bi bi-cart3"></i>

                @if ($mobileCartCount > 0)
                    <span class="store-badge">
                        {{ $mobileCartCount }}
                    </span>
                @endif

                <span>Cart</span>
            </a>

            <a href="{{ route('dashboard') }}">
                <i class="bi bi-person"></i>
                <span>Akun</span>
            </a>

        @else

            <a href="{{ route('login') }}">
                <i class="bi bi-person"></i>
                <span>Login</span>
            </a>

            <a href="{{ route('register') }}">
                <i class="bi bi-person-plus"></i>
                <span>Daftar</span>
            </a>

        @endauth

    </nav>

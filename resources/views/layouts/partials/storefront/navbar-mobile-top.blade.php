<nav class="store-mobile-topbar d-lg-none">
    <div class="container">
        <div class="store-mobile-topbar-inner">

            {{-- Store Brand --}}
            <a
                href="{{ route('storefront.home') }}"
                class="store-mobile-brand"
                aria-label="MeiStore"
            >
                <span class="store-mobile-brand-mark">
                    <i class="bi bi-bag-heart"></i>
                </span>

                Mei<span>Store</span>
            </a>

            {{-- User --}}
            @auth
                <a
                    href="{{ route('dashboard') }}"
                    class="store-mobile-user"
                    aria-label="Akun saya"
                >
                    <i class="bi bi-person-circle"></i>
                </a>
            @else
                <a
                    href="{{ route('login') }}"
                    class="store-mobile-user"
                    aria-label="Login"
                >
                    <i class="bi bi-person-circle"></i>
                </a>
            @endauth

        </div>
    </div>
</nav>
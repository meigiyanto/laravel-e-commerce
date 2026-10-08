<nav class="store-mobile-topbar">
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
            <div class="dropdown store-mobile-user-dropdown">
                <button
                    type="button"
                    class="store-mobile-user"
                    data-bs-toggle="dropdown"
                    aria-expanded="false"
                    aria-label="Akun"
                    title="Akun"
                >
                    <i class="bi bi-person-circle" aria-hidden="true"></i>
                </button>

                <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0">

                    @auth

                        <li class="dropdown-user-info">
                            <div class="dropdown-user-name">
                                {{ auth()->user()->name }}
                            </div>

                            <div class="dropdown-user-email">
                                {{ auth()->user()->email }}
                            </div>
                        </li>

                        <li>
                            <hr class="dropdown-divider">
                        </li>

                        <li>
                            <a
                                class="dropdown-item"
                                href="{{ route('dashboard') }}"
                            >
                                <i class="bi bi-speedometer2"></i>
                                Dashboard
                            </a>
                        </li>

                        <li>
                            <a
                                class="dropdown-item"
                                href="{{ route('profile.edit') }}"
                            >
                                <i class="bi bi-person"></i>
                                My Profile
                            </a>
                        </li>

                        <li>
                            <a
                                class="dropdown-item"
                                href="{{ route('orders.index') }}"
                            >
                                <i class="bi bi-bag"></i>
                                My Order
                            </a>
                        </li>

                        @if (auth()->user()->isAdmin())

                            <li>
                                <hr class="dropdown-divider">
                            </li>

                            <li>
                                <a
                                    class="dropdown-item"
                                    href="{{ route('admin.dashboard') }}"
                                >
                                    <i class="bi bi-shield-check"></i>
                                    Admin Panel
                                </a>
                            </li>

                        @endif

                        <li>
                            <hr class="dropdown-divider">
                        </li>

                        <li>
                            <form
                                method="POST"
                                action="{{ route('logout') }}"
                            >
                                @csrf

                                <button
                                    type="submit"
                                    class="dropdown-item text-danger"
                                >
                                    <i class="bi bi-box-arrow-right"></i>
                                    Logout
                                </button>
                            </form>
                        </li>

                    @else

                        <li>
                            <a
                                class="dropdown-item"
                                href="{{ route('login') }}"
                            >
                                <i class="bi bi-box-arrow-in-right"></i>
                                Login
                            </a>
                        </li>

                        @if (Route::has('register'))

                            <li>
                                <a
                                    class="dropdown-item"
                                    href="{{ route('register') }}"
                                >
                                    <i class="bi bi-person-plus"></i>
                                    Daftar
                                </a>
                            </li>

                        @endif

                    @endauth

                </ul>
            </div>

        </div>
    </div>
</nav>

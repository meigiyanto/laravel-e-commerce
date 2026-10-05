    <header class="store-main-header sticky-top">
        <div class="container">
            <div class="store-header-inner d-flex flex-wrap align-items-center gap-3">

                {{-- Logo --}}
                <a
                    href="{{ route('storefront.home') }}"
                    class="store-brand"
                    aria-label="MeiStore"
                >
                    <span class="store-brand-mark">
                        <i class="bi bi-bag-heart"></i>
                    </span>

                    Mei<span>Store</span>
                </a>


                {{-- Search --}}
                <form
                    class="store-search"
                    action="{{ route('storefront.shop') }}"
                    method="GET"
                >
                    <div class="input-group">

                        <input
                            type="search"
                            name="q"
                            value="{{ request('q') }}"
                            class="form-control"
                            placeholder="Cari produk yang Anda inginkan..."
                            aria-label="Cari produk"
                        >

                        <button
                            type="submit"
                            class="btn"
                            aria-label="Cari"
                        >
                            <i class="bi bi-search"></i>
                        </button>

                    </div>
                </form>


                {{-- Header actions --}}
                <div class="store-header-actions ms-auto">

                    @auth

                        {{-- Wishlist --}}
                        @php
                            $wishlistCount = auth()->user()->wishlistProducts()->count();
                        @endphp

                        <a
                            href="{{ route('wishlist.index') }}"
                            class="store-action d-sm-none d-md-inline-flex"
                            title="Wishlist"
                        >
                            <span class="store-action-icon">
                                <i class="bi bi-heart"></i>

                                @if ($wishlistCount > 0)
                                    <span class="store-badge">
                                        {{ $wishlistCount }}
                                    </span>
                                @endif
                            </span>

                            <span class="store-action-label">
                                <small>Produk</small>
                                <strong>Wishlist</strong>
                            </span>
                        </a>


                        {{-- Compare --}}
                        @php
                            $compareCount = count(session('compare', []));
                        @endphp

                        <a
                            href="{{ route('compare.index') }}"
                            class="store-action d-md-none d-md-inline-flex"
                            title="Compare"
                        >
                            <span class="store-action-icon">
                                <i class="bi bi-bar-chart"></i>

                                @if ($compareCount > 0)
                                    <span class="store-badge">
                                        {{ $compareCount }}
                                    </span>
                                @endif
                            </span>

                            <span class="store-action-label">
                                <small>Produk</small>
                                <strong>Compare</strong>
                            </span>
                        </a>


                        {{-- Cart --}}
                        @php
                            $cartCount = auth()->user()->cart?->items()->sum('quantity') ?? 0;
                        @endphp

                        <a
                            href="{{ route('cart.index') }}"
                            class="store-action d-sm-none d-md-inline-flex"
                            title="Keranjang"
                        >
                            <span class="store-action-icon">
                                <i class="bi bi-cart3"></i>

                                <span
                                    id="cart-count-badge"
                                    class="store-badge {{ $cartCount < 1 ? 'd-none' : '' }}"
                                >
                                    {{ $cartCount }}
                                </span>
                            </span>

                            <span class="store-action-label">
                                <small>Belanja</small>
                                <strong>Keranjang</strong>
                            </span>
                        </a>


                        {{-- Account --}}
                        <div class="dropdown">

                            <button
                                type="button"
                                class="store-action border-0 bg-transparent p-0"
                                data-bs-toggle="dropdown"
                                aria-expanded="false"
                                title="Akun"
                            >
                                <span class="store-action-icon">
                                    <i class="bi bi-person-circle"></i>
                                </span>

                                <span class="store-action-label d-none d-lg-flex">
                                    <small>Halo,</small>
                                    <strong>
                                        {{ \Illuminate\Support\Str::limit(auth()->user()->name, 14) }}
                                    </strong>
                                </span>
                            </button>

                            <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 mt-3">

                                <li class="px-3 py-2">
                                    <div class="fw-bold">
                                        {{ auth()->user()->name }}
                                    </div>

                                    <small class="text-muted">
                                        {{ auth()->user()->email }}
                                    </small>
                                </li>

                                <li>
                                    <hr class="dropdown-divider">
                                </li>

                                <li>
                                    <a
                                        class="dropdown-item"
                                        href="{{ route('dashboard') }}"
                                    >
                                        <i class="bi bi-speedometer2 me-2"></i>
                                        Dashboard
                                    </a>
                                </li>

                                <li>
                                    <a
                                        class="dropdown-item"
                                        href="{{ route('orders.index') }}"
                                    >
                                        <i class="bi bi-bag me-2"></i>
                                        Pesanan Saya
                                    </a>
                                </li>

                                <li>
                                    <a
                                        class="dropdown-item"
                                        href="{{ route('profile.edit') }}"
                                    >
                                        <i class="bi bi-person me-2"></i>
                                        Profil
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
                                            <i class="bi bi-shield-check me-2"></i>
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
                                            <i class="bi bi-box-arrow-right me-2"></i>
                                            Logout
                                        </button>
                                    </form>
                                </li>

                            </ul>

                        </div>

                    @else

                        {{-- Guest --}}
                        <a
                            href="{{ route('login') }}"
                            class="store-action"
                        >
                            <span class="store-action-icon">
                                <i class="bi bi-person-circle"></i>
                            </span>

                            <span class="store-action-label d-none d-lg-flex">
                                <small>Selamat datang</small>
                                <strong>Login / Daftar</strong>
                            </span>
                        </a>

                    @endauth

                </div>

            </div>
        </div>
    </header>

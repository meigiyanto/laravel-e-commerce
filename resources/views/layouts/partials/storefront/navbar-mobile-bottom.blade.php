{{-- =========================================================
        MOBILE BOTTOM NAVIGATION
========================================================== --}}
<nav class="store-mobile-bottom" aria-label="Navigasi utama">

    {{-- MENU --}}
    <button type="button" class="store-mobile-bottom-item" data-mobile-drawer="menu"
        aria-controls="storeMobileMenuDrawer" aria-expanded="false">
        <span class="store-mobile-bottom-icon">
            <i class="bi bi-list"></i>
        </span>

        <span class="store-mobile-bottom-label">
            Menu
        </span>
    </button>


    {{-- KATEGORI --}}
    <button type="button" class="store-mobile-bottom-item" data-mobile-drawer="categories"
        aria-controls="storeMobileCategoryDrawer" aria-expanded="false">
        <span class="store-mobile-bottom-icon">
            <i class="bi bi-grid-3x3-gap"></i>
        </span>

        <span class="store-mobile-bottom-label">
            Kategori
        </span>
    </button>


    {{-- SEARCH --}}
    <button type="button" class="store-mobile-bottom-item" data-mobile-drawer="search"
        aria-controls="storeMobileSearchDrawer" aria-expanded="false">
        <span class="store-mobile-bottom-icon">
            <i class="bi bi-search"></i>
        </span>

        <span class="store-mobile-bottom-label">
            Cari
        </span>
    </button>


    {{-- CART --}}
    @php
    $mobileCartCount = app(\App\Services\CartService::class)->count();
    @endphp

    <button type="button" class="store-mobile-bottom-item" data-mobile-drawer="cart"
        aria-controls="storeMobileCartDrawer" aria-expanded="false">
        <span class="store-mobile-bottom-icon">

            <i class="bi bi-cart3"></i>

            @if ($mobileCartCount > 0)
            <span class="store-mobile-bottom-badge">
                {{ $mobileCartCount > 99 ? '99+' : $mobileCartCount }}
            </span>
            @endif

        </span>

        <span class="store-mobile-bottom-label">
            Cart
        </span>
    </button>

</nav>


{{-- =========================================================
        MOBILE MENU DRAWER
========================================================== --}}
<div id="storeMobileMenuDrawer" class="store-mobile-drawer" aria-hidden="true">
    <div class="store-mobile-drawer-panel">

        <div class="store-mobile-drawer-header">

            <div>
                <span class="store-mobile-drawer-eyebrow">
                    Navigation
                </span>

                <h2 class="store-mobile-drawer-title">
                    Menu
                </h2>
            </div>

            <button type="button" class="store-mobile-drawer-close" data-mobile-drawer-close aria-label="Tutup menu">
                <i class="bi bi-x-lg"></i>
            </button>

        </div>


        <div class="store-mobile-drawer-body">

            {{-- PRIMARY NAVIGATION --}}
            <div class="store-mobile-drawer-section">

                <a href="{{ route('storefront.home') }}"
                    class="store-mobile-drawer-link {{ request()->routeIs('storefront.home') ? 'active' : '' }}">
                    <span class="store-mobile-drawer-link-icon">
                        <i class="bi bi-house"></i>
                    </span>

                    <span class="store-mobile-drawer-link-content">
                        <strong>Home</strong>
                        <small>Halaman utama toko</small>
                    </span>

                    <i class="bi bi-chevron-right"></i>
                </a>


                <a href="{{ route('storefront.shop') }}"
                    class="store-mobile-drawer-link {{ request()->routeIs('storefront.shop', 'storefront.category', 'storefront.product') ? 'active' : '' }}">
                    <span class="store-mobile-drawer-link-icon">
                        <i class="bi bi-shop"></i>
                    </span>

                    <span class="store-mobile-drawer-link-content">
                        <strong>Shop</strong>
                        <small>Jelajahi semua produk</small>
                    </span>

                    <i class="bi bi-chevron-right"></i>
                </a>

            </div>


            {{-- ACCOUNT --}}
            <div class="store-mobile-drawer-section">

                <div class="store-mobile-drawer-section-title">
                    Akun & Pesanan
                </div>

                @auth

                <a href="{{ route('wishlist.index') }}"
                    class="store-mobile-drawer-link {{ request()->routeIs('wishlist.index') ? 'active' : '' }}">
                    <span class="store-mobile-drawer-link-icon">
                        <i class="bi bi-heart"></i>
                    </span>

                    <span class="store-mobile-drawer-link-content">
                        <strong>Wishlist</strong>
                        <small>Produk yang Anda simpan</small>
                    </span>

                    <i class="bi bi-chevron-right"></i>
                </a>


                <a href="{{ route('compare.index') }}"
                    class="store-mobile-drawer-link {{ request()->routeIs('compare.index') ? 'active' : '' }}">
                    <span class="store-mobile-drawer-link-icon">
                        <i class="bi bi-bar-chart"></i>
                    </span>

                    <span class="store-mobile-drawer-link-content">
                        <strong>Compare</strong>
                        <small>Bandingkan produk</small>
                    </span>

                    <i class="bi bi-chevron-right"></i>
                </a>


                <a href="{{ route('orders.index') }}"
                    class="store-mobile-drawer-link {{ request()->routeIs('orders.index') ? 'active' : '' }}">
                    <span class="store-mobile-drawer-link-icon">
                        <i class="bi bi-receipt"></i>
                    </span>

                    <span class="store-mobile-drawer-link-content">
                        <strong>Order</strong>
                        <small>Lihat riwayat pesanan</small>
                    </span>

                    <i class="bi bi-chevron-right"></i>
                </a>

                @else

                <a href="{{ route('login') }}" class="store-mobile-drawer-link">
                    <span class="store-mobile-drawer-link-icon">
                        <i class="bi bi-box-arrow-in-right"></i>
                    </span>

                    <span class="store-mobile-drawer-link-content">
                        <strong>Login</strong>
                        <small>Masuk ke akun Anda</small>
                    </span>

                    <i class="bi bi-chevron-right"></i>
                </a>


                <a href="{{ route('register') }}" class="store-mobile-drawer-link">
                    <span class="store-mobile-drawer-link-icon">
                        <i class="bi bi-person-plus"></i>
                    </span>

                    <span class="store-mobile-drawer-link-content">
                        <strong>Daftar</strong>
                        <small>Buat akun baru</small>
                    </span>

                    <i class="bi bi-chevron-right"></i>
                </a>

                @endauth

            </div>

        </div>

    </div>
</div>


{{-- =========================================================
        MOBILE CATEGORY DRAWER
========================================================== --}}
<div id="storeMobileCategoryDrawer" class="store-mobile-drawer" aria-hidden="true">
    <div class="store-mobile-drawer-panel">

        {{-- HEADER --}}
        <div class="store-mobile-drawer-header">

            <div>
                <span class="store-mobile-drawer-eyebrow">
                    Store
                </span>

                <h2 class="store-mobile-drawer-title">
                    Kategori
                </h2>
            </div>

            <button type="button" class="store-mobile-drawer-close" data-mobile-drawer-close
                aria-label="Tutup kategori">
                <i class="bi bi-x-lg"></i>
            </button>

        </div>


        {{-- BODY --}}
        <div class="store-mobile-drawer-body">

            <div class="store-mobile-category-list">

                @forelse ($storefrontCategories ?? collect() as $category)

                <div class="store-mobile-category-item">

                    {{-- CATEGORY HEADER --}}
                    <button type="button" class="store-mobile-category-toggle" aria-expanded="false"
                        aria-controls="storeMobileSubcategories{{ $category->id }}">

                        <span class="store-mobile-category-icon">
                            <i class="bi bi-grid"></i>
                        </span>

                        <span class="store-mobile-category-content">

                            <strong>
                                {{ $category->name }}
                            </strong>

                            <small>
                                Lihat subkategori
                            </small>

                        </span>

                        <span class="store-mobile-category-arrow">
                            <i class="bi bi-chevron-down"></i>
                        </span>

                    </button>


                    {{-- SUBCATEGORIES --}}
                    <div id="storeMobileSubcategories{{ $category->id }}" class="store-mobile-subcategory-list"
                        aria-hidden="true">

                        @forelse ($category->subCategories as $subCategory)

                        <a href="{{ route('storefront.shop', [
                                        'category' => $category->slug,
                                        'subcategory' => $subCategory->slug,
                                    ]) }}" class="store-mobile-subcategory-link">
                            <span>
                                {{ $subCategory->name }}
                            </span>

                            <i class="bi bi-chevron-right"></i>
                        </a>

                        @empty

                        <div class="store-mobile-subcategory-empty">
                            Belum ada subkategori.
                        </div>

                        @endforelse


                        {{-- ALL PRODUCTS IN CATEGORY --}}
                        <a href="{{ route('storefront.category', $category->slug) }}" class="store-mobile-category-all">
                            <span>
                                <i class="bi bi-shop"></i>
                                Semua produk {{ $category->name }}
                            </span>

                            <i class="bi bi-arrow-right"></i>
                        </a>

                    </div>

                </div>

                @empty

                <div class="store-mobile-category-empty">
                    <i class="bi bi-grid"></i>

                    <strong>
                        Belum ada kategori
                    </strong>

                    <span>
                        Kategori produk belum tersedia.
                    </span>
                </div>

                @endforelse

            </div>

        </div>

    </div>
</div>

{{-- =========================================================
        MOBILE SEARCH DRAWER
========================================================== --}}
<div id="storeMobileSearchDrawer" class="store-mobile-drawer store-mobile-search-drawer" aria-hidden="true">
    <div class="store-mobile-drawer-panel">

        {{-- HEADER --}}
        <div class="store-mobile-drawer-header">

            <div>
                <span class="store-mobile-drawer-eyebrow">
                    Discovery
                </span>

                <h2 class="store-mobile-drawer-title">
                    Cari Produk
                </h2>
            </div>

            <button type="button" class="store-mobile-drawer-close" data-mobile-drawer-close
                aria-label="Tutup pencarian">
                <i class="bi bi-x-lg"></i>
            </button>

        </div>


        {{-- BODY --}}
        <div class="store-mobile-drawer-body">

            <form action="{{ route('storefront.shop') }}" method="GET" class="store-mobile-search-form">

                <label for="storeMobileSearchInput" class="store-mobile-search-label">
                    Apa yang sedang Anda cari?
                </label>


                <div class="store-mobile-search-input-wrap">

                    <i class="bi bi-search store-mobile-search-input-icon" aria-hidden="true"></i>

                    <input type="search" id="storeMobileSearchInput" name="q" value="{{ request('q') }}"
                        class="store-mobile-search-input" placeholder="Cari produk..." autocomplete="off"
                        enterkeyhint="search" spellcheck="false">

                    <button type="button" class="store-mobile-search-clear" data-mobile-search-clear
                        aria-label="Hapus pencarian" hidden>
                        <i class="bi bi-x-circle-fill"></i>
                    </button>

                </div>


                <button type="submit" class="store-mobile-search-submit">
                    <i class="bi bi-search"></i>
                    <span>Cari Produk</span>
                </button>

            </form>


            {{-- CURRENT SEARCH --}}
            @if (request()->filled('q'))

            <div class="store-mobile-search-current">

                <span class="store-mobile-search-current-label">
                    Pencarian aktif
                </span>

                <strong>
                    "{{ request('q') }}"
                </strong>

            </div>

            @endif


            {{-- QUICK SEARCH --}}
            <div class="store-mobile-search-section">

                <div class="store-mobile-search-section-title">
                    Jelajahi
                </div>

                <a href="{{ route('storefront.shop') }}" class="store-mobile-search-link">
                    <span class="store-mobile-search-link-icon">
                        <i class="bi bi-grid"></i>
                    </span>

                    <span class="store-mobile-search-link-content">
                        <strong>Semua Produk</strong>
                        <small>Lihat seluruh katalog produk</small>
                    </span>

                    <i class="bi bi-chevron-right"></i>
                </a>


                <a href="{{ route('storefront.home') }}" class="store-mobile-search-link">
                    <span class="store-mobile-search-link-icon">
                        <i class="bi bi-house"></i>
                    </span>

                    <span class="store-mobile-search-link-content">
                        <strong>Home</strong>
                        <small>Kembali ke halaman utama</small>
                    </span>

                    <i class="bi bi-chevron-right"></i>
                </a>

            </div>

        </div>

    </div>
</div>

{{-- =========================================================
        MOBILE CART DRAWER
========================================================== --}}
@php
$mobileCartService = app(\App\Services\CartService::class);
$mobileCartCount = $mobileCartService->count();

if (auth()->check()) {
$mobileCart = $mobileCartService->getUserCartWithItems(auth()->id());
$mobileCartItems = $mobileCart->items;
} else {
$mobileGuestCart = $mobileCartService->getGuestCart();

$mobileCartProducts = empty($mobileGuestCart)
? collect()
: \App\Models\Product::with([
'category',
'subCategory',
])
->whereIn('id', array_keys($mobileGuestCart))
->get();

$mobileCartItems = $mobileCartProducts->map(function ($product) use ($mobileGuestCart) {
$quantity = (int) (
$mobileGuestCart[(string) $product->id]
?? $mobileGuestCart[$product->id]
?? 0
);

return (object) [
'id' => null,
'product' => $product,
'quantity' => $quantity,
];
})->filter(function ($item) {
return $item->quantity > 0;
});
}

$mobileCartTotal = $mobileCartItems->sum(function ($item) {
return $item->product->price * $item->quantity;
});
@endphp


<div id="storeMobileCartDrawer" class="store-mobile-drawer store-mobile-cart-drawer" aria-hidden="true">
    <div class="store-mobile-drawer-panel">

        {{-- HEADER --}}
        <div class="store-mobile-drawer-header">

            <div>
                <span class="store-mobile-drawer-eyebrow">
                    Shopping Cart
                </span>

                <h2 class="store-mobile-drawer-title">
                    Keranjang
                </h2>
            </div>

            <button type="button" class="store-mobile-drawer-close" data-mobile-drawer-close
                aria-label="Tutup keranjang">
                <i class="bi bi-x-lg"></i>
            </button>

        </div>


        {{-- BODY --}}
        <div class="store-mobile-drawer-body">

            @if ($mobileCartItems->isNotEmpty())

            <div class="store-mobile-cart-list">

                @foreach ($mobileCartItems as $cartItem)

                @php
                $product = $cartItem->product;
                $quantity = (int) $cartItem->quantity;
                $lineTotal = $product->price * $quantity;
                @endphp

                <article class="store-mobile-cart-item" data-mobile-cart-item>

                    {{-- IMAGE --}}
                    <a href="{{ route('storefront.product', $product->slug) }}" class="store-mobile-cart-image"
                        aria-label="Lihat {{ $product->name }}">
                        @if ($product->image_url)

                        <img src="{{ $product->image_url }}" alt="{{ $product->name }}" loading="lazy">

                        @else

                        <span>
                            <i class="bi bi-image"></i>
                        </span>

                        @endif
                    </a>


                    {{-- CONTENT --}}
                    <div class="store-mobile-cart-content">

                        <a href="{{ route('storefront.product', $product->slug) }}" class="store-mobile-cart-name">
                            {{ $product->name }}
                        </a>

                        <span class="store-mobile-cart-price">
                            Rp {{ number_format($product->price, 0, ',', '.') }}
                        </span>


                        <div class="store-mobile-cart-meta">

                            {{-- QUANTITY --}}
                            <div class="store-mobile-cart-quantity">

                                <form action="{{ route('cart.update', $product) }}" method="POST"
                                    class="store-mobile-cart-quantity-form">
                                    @csrf
                                    @method('PATCH')

                                    <button type="button" class="store-mobile-cart-quantity-button"
                                        data-mobile-cart-decrease aria-label="Kurangi {{ $product->name }}">
                                        <i class="bi bi-dash"></i>
                                    </button>

                                    <input type="number" name="quantity" value="{{ $quantity }}" min="1"
                                        max="{{ max(1, $product->stock) }}" class="store-mobile-cart-quantity-input"
                                        inputmode="numeric" aria-label="Jumlah {{ $product->name }}">

                                    <button type="button" class="store-mobile-cart-quantity-button"
                                        data-mobile-cart-increase aria-label="Tambah {{ $product->name }}">
                                        <i class="bi bi-plus"></i>
                                    </button>

                                </form>

                            </div>


                            {{-- REMOVE --}}
                            <form action="{{ route('cart.destroy', $product) }}" method="POST"
                                class="store-mobile-cart-remove-form">
                                @csrf
                                @method('DELETE')

                                <button type="submit" class="store-mobile-cart-remove"
                                    aria-label="Hapus {{ $product->name }} dari keranjang">
                                    <i class="bi bi-trash3"></i>
                                </button>
                            </form>

                        </div>


                        <div class="store-mobile-cart-line-total">
                            Rp {{ number_format($lineTotal, 0, ',', '.') }}
                        </div>

                    </div>

                </article>

                @endforeach

            </div>


            {{-- SUMMARY --}}
            <div class="store-mobile-cart-summary">

                <div class="store-mobile-cart-summary-row">
                    <span>
                        {{ $mobileCartCount }} item
                    </span>

                    <strong>
                        Rp {{ number_format($mobileCartTotal, 0, ',', '.') }}
                    </strong>
                </div>

            </div>


            {{-- ACTIONS --}}
            <div class="store-mobile-cart-actions">

                <a href="{{ route('cart.index') }}" class="store-mobile-cart-view-button">
                    <i class="bi bi-cart3"></i>
                    View Cart
                </a>


                @auth

                <a href="{{ route('checkout.index') }}" class="store-mobile-cart-checkout-button">
                    Checkout
                    <i class="bi bi-arrow-right"></i>
                </a>

                @else

                <a href="{{ route('login') }}" class="store-mobile-cart-checkout-button">
                    Login untuk Checkout
                    <i class="bi bi-arrow-right"></i>
                </a>

                @endauth

            </div>

            @else

            {{-- EMPTY CART --}}
            <div class="store-mobile-cart-empty">

                <div class="store-mobile-cart-empty-icon">
                    <i class="bi bi-cart3"></i>
                </div>

                <h3>
                    Keranjang masih kosong
                </h3>

                <p>
                    Tambahkan produk yang Anda sukai
                    ke keranjang untuk melanjutkan.
                </p>

                <a href="{{ route('storefront.shop') }}" class="store-mobile-cart-empty-button">
                    <i class="bi bi-shop"></i>
                    Mulai Belanja
                </a>

            </div>

            @endif

        </div>

    </div>
</div>
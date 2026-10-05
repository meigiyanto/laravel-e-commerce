@props([
    'product',
    'showCategory' => true,
    'showDescription' => false,
])

@php
    $rating = (float) ($product->reviews_avg_rating ?? 0);
    $reviewCount = (int) ($product->reviews_count ?? 0);
@endphp

<article class="store-product-card">
    {{-- Product image --}}
    <div class="store-product-image-wrap">

        <a
            href="{{ route('storefront.product', $product->slug) }}"
            class="store-product-image-link"
            aria-label="Lihat {{ $product->name }}"
        >

            @if ($product->image_url)
                <img
                    src="{{ $product->image_url }}"
                    alt="{{ $product->name }}"
                    class="store-product-image"
                    loading="lazy"
                >

            @else

                <div
                    class="store-product-placeholder"
                    role="img"
                    aria-label="Gambar {{ $product->name }} tidak tersedia"
                >
                    <i class="bi bi-image"></i>
                </div>
            @endif
        </a>


        {{-- Product actions --}}
        @auth
            <div class="store-product-actions">

                <form
                    action="{{ route('wishlist.store', $product) }}"
                    class="wishlist-form"
                    method="POST"
                >
                    @csrf

                    <button
                        type="submit"
                        class="store-product-action"
                        title="Tambah ke wishlist"
                        aria-label="Tambah {{ $product->name }} ke wishlist"
                    >
                        <i class="bi bi-heart"></i>
                    </button>
                </form>


                <form
                    action="{{ route('compare.store', $product) }}"
                    class="compare-form"
                    method="POST"
                >
                    @csrf

                    <button
                        type="submit"
                        class="store-product-action"
                        title="Bandingkan produk"
                        aria-label="Bandingkan {{ $product->name }}"
                    >
                        <i class="bi bi-bar-chart"></i>
                    </button>
                </form>

            </div>

        @endauth

    </div>


    {{-- Product body --}}
    <div class="store-product-body">

        {{-- Category --}}
        @if ($showCategory && $product->category)

            <div class="store-product-category">

                <a
                    href="{{ route('storefront.category', $product->category->slug) }}"
                >
                    {{ $product->category->name }}
                </a>

            </div>

        @endif


        {{-- Product name --}}
        <h3 class="store-product-name">

            <a
                href="{{ route('storefront.product', $product->slug) }}"
            >
                {{ $product->name }}
            </a>

        </h3>


        {{-- Rating --}}
        <div class="store-product-rating">

            <span class="store-rating-stars" aria-label="Rating {{ number_format($rating, 1) }} dari 5">

                @for ($i = 1; $i <= 5; $i++)

                    @if ($rating >= $i)

                        <i class="bi bi-star-fill"></i>

                    @elseif ($rating >= ($i - 0.5))

                        <i class="bi bi-star-half"></i>

                    @else

                        <i class="bi bi-star"></i>

                    @endif

                @endfor

            </span>


            @if ($reviewCount > 0)

                <span class="store-review-count">
                    ({{ $reviewCount }})
                </span>

            @else

                <span class="store-review-count">
                    Belum ada review
                </span>

            @endif

        </div>


        {{-- Price --}}
        <div class="store-product-price">

            Rp {{ number_format($product->price, 0, ',', '.') }}

        </div>


        {{-- Optional description --}}
        @if ($showDescription && $product->description)

            <p class="store-product-description">

                {{ \Illuminate\Support\Str::limit($product->description, 90) }}

            </p>

        @endif


        {{-- Stock --}}
        <div class="store-product-stock">

            @if ($product->stock > 0)

                <span class="store-stock-available">
                    <i class="bi bi-check-circle"></i>
                    {{ $product->stock }} tersedia
                </span>

            @else

                <span class="store-stock-empty">
                    <i class="bi bi-x-circle"></i>
                    Stok habis
                </span>

            @endif

        </div>


        {{-- Cart action --}}
        @if ($product->stock > 0)

            @auth

                <form
                    action="{{ route('cart.store') }}"
                    method="POST"
                    class="store-product-cart-form add-to-cart-form"
                >
                    @csrf

                    <input
                        type="hidden"
                        name="product_id"
                        value="{{ $product->id }}"
                    >

                    <input
                        type="hidden"
                        name="quantity"
                        value="1"
                    >

                    <button
                        type="submit"
                        class="store-add-cart add-to-cart-button"
                    >
                        <i class="bi bi-cart-plus"></i>
                        Tambah ke Keranjang
                    </button>

                </form>

            @else

                <a
                    href="{{ route('login') }}"
                    class="store-add-cart"
                >
                    <i class="bi bi-person"></i>
                    Login untuk membeli
                </a>

            @endauth

        @else

            <button
                type="button"
                class="store-add-cart store-add-cart-disabled"
                disabled
            >
                <i class="bi bi-cart-x"></i>
                Stok Habis
            </button>

        @endif

    </div>
</article>

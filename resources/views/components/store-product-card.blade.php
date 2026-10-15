@props([
    'product',
    'showCategory' => true,
    'showDescription' => false,
])

@php
    $purchasableVariants = $product->variants
        ->where('status', 'active')
        ->sortByDesc('is_default')
        ->values();

    $hasVariants = $purchasableVariants->isNotEmpty();

    $selectedVariant = $purchasableVariants->first(
        fn ($variant) => $variant->is_default
    ) ?? $purchasableVariants->first();

    $selectedAvailable = $hasVariants
        ? (int) ($selectedVariant?->inventory?->available_quantity ?? 0)
        : (int) $product->stock;

    $selectedPrice = $selectedVariant?->price ?? $product->price;

    $rating = (float) ($product->reviews_avg_rating ?? 0);
    $reviewCount = (int) ($product->reviews_count ?? 0);
@endphp

<article class="store-product-card">
    {{-- Gambar produk --}}
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

    <div class="store-product-body">
        {{-- Kategori --}}
        @if ($showCategory && $product->category)
            <div class="store-product-category">
                <a href="{{ route('storefront.category', $product->category->slug) }}">
                    {{ $product->category->name }}
                </a>
            </div>
        @endif

        {{-- Nama produk --}}
        <h3 class="store-product-name">
            <a href="{{ route('storefront.product', $product->slug) }}">
                {{ $product->name }}
            </a>
        </h3>

        {{-- Rating --}}
        <div class="store-product-rating">
            <span
                class="store-rating-stars"
                aria-label="Rating {{ number_format($rating, 1) }} dari 5"
            >
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

            <span class="store-review-count">
                {{ $reviewCount > 0 ? '(' . $reviewCount . ')' : 'Belum ada review' }}
            </span>
        </div>

        {{-- Harga --}}
        <div class="store-product-price js-variant-price">
            Rp {{ number_format($selectedPrice, 0, ',', '.') }}
        </div>

        @if ($showDescription && $product->description)
            <p class="store-product-description">
                {{ \Illuminate\Support\Str::limit($product->description, 90) }}
            </p>
        @endif

        {{-- Form pembelian: satu form dan satu input product_variant_id --}}
        <form
            action="{{ route('cart.store') }}"
            method="POST"
            class="store-product-cart-form js-product-cart-form"
        >
            @csrf

            <input
                type="hidden"
                name="product_id"
                value="{{ $product->id }}"
            >

            @if ($hasVariants)
                <div class="store-card-variant">
                    <label class="store-card-control-label">
                        Varian
                    </label>

                    <select
                        name="product_variant_id"
                        class="form-select form-select-sm js-variant-select"
                        aria-label="Pilih varian {{ $product->name }}"
                        required
                    >
                        @foreach ($purchasableVariants as $variant)
                            @php
                                $available = (int) (
                                    $variant->inventory?->available_quantity ?? 0
                                );

                                $attributeLabel = $variant->attributeValues
                                    ->map(fn ($value) =>
                                        ($value->attribute?->name
                                            ? $value->attribute->name . ': '
                                            : '') . $value->value
                                    )
                                    ->implode(' / ');

                                $variantLabel = $attributeLabel
                                    ?: ($variant->name ?: $variant->sku ?: 'Varian');

                                $optionLabel = $variantLabel
                                    . ($available < 1 ? ' — Habis' : '');
                            @endphp

                            <option
                                value="{{ $variant->id }}"
                                data-price="{{ $variant->price }}"
                                data-stock="{{ $available }}"
                                @selected($selectedVariant?->id === $variant->id)
                                @disabled($available < 1)
                            >
                                {{ $optionLabel }}
                            </option>
                        @endforeach
                    </select>
                </div>
            @endif

            {{-- Stok varian terpilih --}}
            <div
                class="store-card-stock js-variant-stock {{ $selectedAvailable > 0 ? 'is-available' : 'is-empty' }}"
                aria-live="polite"
            >
                @if ($selectedAvailable > 0)
                    <i class="bi bi-check-circle-fill" aria-hidden="true"></i>
                    <span>{{ $selectedAvailable }} unit tersedia</span>
                @else
                    <i class="bi bi-x-circle-fill" aria-hidden="true"></i>
                    <span>Stok habis</span>
                @endif
            </div>

            <div class="store-card-purchase-row">
                <div class="store-card-quantity">
                    <label class="store-card-control-label">
                        Jumlah
                    </label>

                    <input
                        type="number"
                        name="quantity"
                        value="1"
                        min="1"
                        max="{{ max(1, $selectedAvailable) }}"
                        class="form-control form-control-sm js-quantity-input"
                        aria-label="Jumlah {{ $product->name }}"
                        required
                        @disabled($selectedAvailable < 1)
                    >
                </div>

                <button
                    type="submit"
                    class="store-add-cart store-card-submit js-add-to-cart"
                    @disabled($selectedAvailable < 1)
                >
                    <i class="bi bi-cart-plus" aria-hidden="true"></i>
                    <span>Tambah</span>
                </button>
            </div>
        </form>
    </div>
</article>
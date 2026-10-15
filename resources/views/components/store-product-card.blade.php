@props([
    'product',
    'purchasableVariants' => collect(),
    'selectedAvailable' => true,
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
                        title="Add to wishlist"
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
        <div
            class="product-detail-price"
            id="selected-variant-price"
        >
            Rp {{ number_format(
                $selectedVariant?->price ?? $product->price,
                0,
                ',',
                '.'
            ) }}
        </div>
        
        {{-- Optional description --}}
        @if ($showDescription && $product->description)
            <p class="store-product-description">
                {{ \Illuminate\Support\Str::limit($product->description, 90) }}
            </p>
        @endif

        
        @if ($purchasableVariants->isNotEmpty())
            <div class="mb-3">
                <label for="product_variant_id" class="form-label">
                    Pilih varian
                </label>
        
                <select
                    id="product_variant_id"
                    name="product_variant_id"
                    class="form-select"
                    form="add-product-to-cart"
                    required
                >
                    @foreach ($purchasableVariants as $variant)
                        @php
                            $available = $variant->inventory
                                ? $variant->inventory->available_quantity
                                : 0;
        
                            $attributeLabel = $variant->attributeValues
                                ->map(fn ($value) =>
                                    $value->attribute->name . ': ' . $value->value
                                )
                                ->implode(' / ');
        
                            $variantLabel = $attributeLabel
                                ?: ($variant->name ?: $variant->sku);
                        @endphp
        
                        <option
                            value="{{ $variant->id }}"
                            data-price="{{ $variant->price }}"
                            data-stock="{{ $available }}"
                            @selected($selectedVariant?->id === $variant->id)
                            @disabled($available < 1)
                        >
                            {{ $variantLabel }}
                            — Rp {{ number_format($variant->price, 0, ',', '.') }}
                            @if ($available < 1)
                                (Stok habis)
                            @else
                                (Stok {{ $available }})
                            @endif
                        </option>
                    @endforeach
                </select>
            </div>
        @endif
        
        {{-- Stock --}}
        @if ($product->stock > 0)
            <div class="product-stock" id="selected-variant-stock">
            @if ($selectedAvailable > 0)
                <span class="stock-status stock-available">
                    <i class="bi bi-check-circle-fill"></i>
                    Stok tersedia
                </span>
                <span class="stock-count">
                    {{ $selectedAvailable }} unit tersedia
                </span>
            @else
                <span class="stock-status stock-empty">
                    <i class="bi bi-x-circle-fill"></i>
                    Stok habis
                </span>
            @endif
        </div>
        @else
            <span class="store-stock-empty">
                <i class="bi bi-x-circle"></i>
                Stok habis
            </span>
        @endif
        
        @if ($product->stock > 0)
            @auth
                <form
                    id="add-product-to-cart"
                    action="{{ route('cart.store') }}"
                    method="POST"
                    class="product-purchase-form add-to-cart-form"
                >
                    @csrf
                    @php
                        $defaultVariant = $product->variants
                            ->firstWhere('is_default', true)
                            ?? $product->variants->first();
                    @endphp
                    
                    @if ($defaultVariant)
                        <input
                            type="hidden"
                            name="product_variant_id"
                            value="{{ $defaultVariant->id }}"
                        >
                    @endif
                    <input
                        type="hidden"
                        name="product_id"
                        value="{{ $product->id }}"
                    >
            
                    <div class="purchase-row">
                        <div class="quantity-control">
                            <label for="quantity">Jumlah</label>
            
                            <input
                                type="number"
                                id="quantity"
                                name="quantity"
                                value="1"
                                min="1"
                                max="{{ max(1, $selectedAvailable) }}"
                                required
                                @disabled($selectedAvailable < 1)
                            >
                        </div>
            
                        <div class="purchase-button-wrap">
                            <label class="d-none d-sm-block">&nbsp;</label>
            
                            <button
                                type="submit"
                                class="store-add-cart add-to-cart-button"
                                id="add-to-cart-submit"
                                @disabled($selectedAvailable < 1)
                            >
                                <i class="bi bi-cart-plus"></i>
                                Tambah ke Keranjang
                            </button>
                        </div>
                    </div>
                </form>
            @endauth
        @else
            <button
                type="button"
                class="store-add-cart store-add-cart-disabled"
                disabled
            >
                <i class="bi bi-cart-x" aria-hidden="true"></i>
                Stok habis
            </button>

        @endif
    </div>
</article>

@if ($mobileCartItems->isNotEmpty())
    <div class="store-mobile-cart-list">
        @foreach ($mobileCartItems as $cartItem)
            @php
                $product = $cartItem->product;
                $quantity = (int) $cartItem->quantity;
                $variant = $cartItem->variant ?? null;
                $price = $variant?->price ?? $product->price;
                $lineTotal = (float) $price * $quantity;
                $inventory = $variant?->inventory;
                $availableStock = $inventory ? max(0, (int) $inventory->quantity - (int) $inventory->reserved_quantity) : max(0, (int) $product->stock);

            @endphp

            <article class="store-mobile-cart-item" data-mobile-cart-item data-product-id="{{ $product->id }}">
                {{-- IMAGE --}}
                <a href="{{ route('storefront.product', $product->slug) }}" class="store-mobile-cart-image" aria-label="View {{ $product->name }}">
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
                    
                    @if ($variant)
                        <div class="store-mobile-cart-variant">
                            {{ $variant->name ?: $variant->sku }}
                    
                            @if ($variant->attributeValues->isNotEmpty())
                                — {{ $variant->attributeValues
                                    ->map(fn ($value) => $value->value)
                                    ->implode(' / ') }}
                            @endif
                        </div>
                    @endif
                    
                    <span class="store-mobile-cart-price">Rp {{ number_format($price, 0, ',', '.') }}</span>

                    <div class="store-mobile-cart-meta">
                        {{-- QUANTITY --}}
                        <div class="store-mobile-cart-quantity">
                            
                            <form
                                action="{{ auth()->check()
                                    ? route('cart.items.update', $cartItem->id)
                                    : route('cart.product.update', $cartItem->product_variant_id) }}"
                                method="POST"
                                class="store-mobile-cart-quantity-form"
                                data-product-id="{{ $product->id }}"
                                data-variant-id="{{ $cartItem->product_variant_id }}"
                            >
                                @csrf
                                @method('PATCH')
                            
                                <button
                                    type="button"
                                    class="store-mobile-cart-quantity-button"
                                    data-mobile-cart-decrease
                                    aria-label="Decrease {{ $product->name }}"
                                >
                                    <i class="bi bi-dash"></i>
                                </button>
                            
                                <input
                                    type="number"
                                    name="quantity"
                                    value="{{ $quantity }}"
                                    min="1"
                                    max="{{ $availableStock }}"
                                    class="store-mobile-cart-quantity-input"
                                    inputmode="numeric"
                                    aria-label="Jumlah {{ $product->name }}"
                                    data-available-stock="{{ $availableStock }}"
                                >

                                <button
                                    type="button"
                                    class="store-mobile-cart-quantity-button"
                                    data-mobile-cart-increase
                                    aria-label="Increase {{ $product->name }}"
                                >
                                    <i class="bi bi-plus"></i>
                                </button>
                            </form>

                        </div>
                    </div>

                    <div class="store-mobile-cart-line-total">
                        Rp {{ number_format($lineTotal, 0, ',', '.') }}
                    </div>
                </div>
                
                <form
                    action="{{ auth()->check()
                        ? route('cart.items.destroy', $cartItem->id)
                        : route('cart.product.destroy', $cartItem->product_variant_id) }}"
                    method="POST"
                    class="store-mobile-cart-remove-form"
                    data-product-id="{{ $product->id }}"
                    data-variant-id="{{ $cartItem->product_variant_id }}"
                >
                    @csrf
                    @method('DELETE')
                
                    <button
                        type="submit"
                        class="store-mobile-cart-remove"
                        aria-label="Hapus {{ $product->name }} — {{ $variant?->name ?? $variant?->sku ?? 'Produk' }}"
                    >
                        <i class="bi bi-trash3" aria-hidden="true"></i>
                    </button>
                </form>

            </article>
        @endforeach
    </div>


    {{-- SUMMARY --}}
    <div class="store-mobile-cart-summary">
        <div class="store-mobile-cart-summary-row">
            <span>{{ $mobileCartCount }} item</span>
            <strong>Rp {{ number_format($mobileCartTotal, 0, ',', '.') }}</strong>
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

        <h3>Keranjang masih kosong</h3>
        <p>Tambahkan produk yang Anda sukai ke keranjang untuk melanjutkan.</p>

        <a href="{{ route('storefront.shop') }}" class="store-mobile-cart-empty-button">
            <i class="bi bi-shop"></i>
            Mulai Belanja
        </a>

    </div>
@endif

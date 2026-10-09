@if ($mobileCartItems->isNotEmpty())
    <div class="store-mobile-cart-list">
        @foreach ($mobileCartItems as $cartItem)
            @php
                $product = $cartItem->product;
                $quantity = (int) $cartItem->quantity;
                $lineTotal = $product->price * $quantity;
            @endphp

            <article
                class="store-mobile-cart-item"
                data-mobile-cart-item
                data-product-id="{{ $product->id }}"
            >

                {{-- IMAGE --}}
                <a
                    href="{{ route('storefront.product', $product->slug) }}"
                    class="store-mobile-cart-image"
                    aria-label="View {{ $product->name }}"
                >
                    @if ($product->image_url)
                        <img
                            src="{{ $product->image_url }}"
                            alt="{{ $product->name }}"
                            loading="lazy"
                        >
                    @else
                        <span>
                            <i class="bi bi-image"></i>
                        </span>
                    @endif
                </a>


                {{-- CONTENT --}}
                <div class="store-mobile-cart-content">

                    <a
                        href="{{ route('storefront.product', $product->slug) }}"
                        class="store-mobile-cart-name"
                    >
                        {{ $product->name }}
                    </a>

                    <span class="store-mobile-cart-price">
                        Rp {{ number_format($product->price, 0, ',', '.') }}
                    </span>


                    <div class="store-mobile-cart-meta">

                        {{-- QUANTITY --}}
                        <div class="store-mobile-cart-quantity">

                            <form
                                action="{{ route('cart.update', $product) }}"
                                method="POST"
                                class="store-mobile-cart-quantity-form"
                                data-product-id="{{ $product->id }}"
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
                                    max="{{ max(1, $product->stock) }}"
                                    class="store-mobile-cart-quantity-input"
                                    inputmode="numeric"
                                    aria-label="Total {{ $product->name }}"
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


                        {{-- REMOVE --}}
                        <form
                            action="{{ route('cart.destroy', $product) }}"
                            method="POST"
                            class="store-mobile-cart-remove-form"
                            data-product-id="{{ $product->id }}"
                        >
                            @csrf
                            @method('DELETE')

                            <button
                                type="submit"
                                class="store-mobile-cart-remove"
                                aria-label="Remove {{ $product->name }}"
                                title="Remove Product"
                            >
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

        <a
            href="{{ route('cart.index') }}"
            class="store-mobile-cart-view-button"
        >
            <i class="bi bi-cart3"></i>
            View Cart
        </a>

        @auth

            <a
                href="{{ route('checkout.index') }}"
                class="store-mobile-cart-checkout-button"
            >
                Checkout
                <i class="bi bi-arrow-right"></i>
            </a>

        @else

            <a
                href="{{ route('login') }}"
                class="store-mobile-cart-checkout-button"
            >
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

        <a
            href="{{ route('storefront.shop') }}"
            class="store-mobile-cart-empty-button"
        >
            <i class="bi bi-shop"></i>
            Mulai Belanja
        </a>

    </div>

@endif

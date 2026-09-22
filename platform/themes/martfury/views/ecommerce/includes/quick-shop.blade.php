<div class="ps-product__header">
    <div class="ps-product__info">
        <h1>{!! BaseHelper::clean($product->name) !!}</h1>
        {!! Theme::partial('product-price', [
            'product' => $product,
            'priceWrapperTag' => 'h4',
            'priceWrapperClass' => 'ps-product__price',
        ]) !!}

        @if ($product->has_variation)
            <div class="pr_switch_wrap">
                {!! render_product_swatches($product, [
                    'selected' => $selectedAttrs,
                    'view'     => Theme::getThemeNamespace() . '::views.ecommerce.attributes.swatches-renderer',
                    'referenceProduct' => $referenceProduct ?? null,
                ]) !!}
            </div>
            <div class="number-items-available" style="display: none; margin-bottom: 10px;"></div>
        @endif

        @if ($product->options()->count() > 0 && isset($product->toArray()['options']))
            <div class="pr_switch_wrap" id="product-option">
                {!! render_product_options($product) !!}
            </div>
        @endif

        <form class="add-to-cart-form" method="POST" action="{{ route('public.cart.add-to-cart') }}" data-bb-toggle="product-form">
            @csrf
            <div class="ps-product__shopping d-block">
                <input type="hidden" name="id" class="hidden-product-id" value="{{ ($product->is_variation || !$product->defaultVariation->product_id) ? $product->id : $product->defaultVariation->product_id }}"/>
                <figure>
                    <figcaption>{{ __('Quantity') }}</figcaption>
                    <div class="form-group--number product__qty">
                        <button class="up" type="button"><i class="icon-plus"></i></button>
                        <button class="down" type="button"><i class="icon-minus"></i></button>
                        <input class="form-control qty-input" type="number" name="qty" value="1" placeholder="1" min="1">
                    </div>
                </figure>
                @if (EcommerceHelper::isCartEnabled())
                    <div class="mt-4 ps-product__actions">
                        <button class="ps-btn ps-btn--black" type="submit" name="add-to-cart" @disabled($product->isOutOfStock())>{{ __('Add to cart') }}</button>
                        @if (EcommerceHelper::isQuickBuyButtonEnabled())
                            <button class="ps-btn" type="submit" name="checkout" @disabled($product->isOutOfStock())>{{ __('Buy Now') }}</button>
                        @endif
                    </div>
                @endif
            </div>
        </form>

        <div class="ps-product__specification">
            <a href="{{ $product->url }}" class="report">{{ __('View full details') }}</a>
        </div>
    </div>
</div>

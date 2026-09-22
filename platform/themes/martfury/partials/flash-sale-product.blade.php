<div class="ps-product__thumbnail">
    <a href="{{ $product->url }}" title="{{ $product->name }}">
        {!! RvMedia::image($product->image, $product->name, 'small') !!}
    </a>
    @if ($product->isOutOfStock())
        <div class="ps-product__badges">
            <span class="ps-product__badge out-stock">{{ __('Out Of Stock') }}</span>
        </div>
    @else
        @if ($product->productLabels->isNotEmpty())
            <div class="ps-product__badges">
                @foreach ($product->productLabels as $label)
                    <span class="ps-product__badge" {!! $label->css_styles !!}>{{ $label->name }}</span>
                @endforeach
            </div>
        @else
            @if ($product->front_sale_price !== $product->price)
                <div class="ps-product__badges">
                    <div class="ps-product__badge">{{ get_sale_percentage($product->price, $product->front_sale_price) }}</div>
                </div>
            @endif
        @endif
    @endif
    <ul class="ps-product__actions">
        @if (EcommerceHelper::isCartEnabled())
            @if ($product->has_variation)
                <li><a href="#" data-bb-toggle="quick-shop" data-url="{{ route('public.ajax.quick-shop', $product->slug) }}" {!! EcommerceHelper::jsAttributes('quick-shop', $product) !!} title="{{ __('Select Options') }}"><i class="icon-bag2"></i></a></li>
            @else
                <li><a class="add-to-cart-button" data-id="{{ $product->id }}" href="#" data-url="{{ route('public.cart.add-to-cart') }}" {!! EcommerceHelper::jsAttributes('add-to-cart', $product, additional: ['data-bb-toggle' => 'none']) !!} title="{{ __('Add To Cart') }}"><i class="icon-bag2"></i></a></li>
            @endif
        @endif
        <li><a href="#" data-url="{{ route('public.ajax.quick-view', $product->id) }}" title="{{ __('Quick View') }}" class="js-quick-view-button"><i class="icon-eye"></i></a></li>
        @if (EcommerceHelper::isWishlistEnabled())
            <li><a class="js-add-to-wishlist-button" href="#" data-url="{{ route('public.wishlist.add', $product->id) }}" title="{{ __('Add to Wishlist') }}"><i class="icon-heart"></i></a></li>
        @endif
        @if (EcommerceHelper::isCompareEnabled())
            <li><a class="js-add-to-compare-button" href="#" data-url="{{ route('public.compare.add', $product->id) }}" title="{{ __('Compare') }}"><i class="icon-chart-bars"></i></a></li>
        @endif
    </ul>
</div>
<div class="ps-product__container">
    {!! Theme::partial('product-price', [
        'product' => $product,
        'priceWrapperTag' => 'p',
        'priceWrapperClass' => 'ps-product__price',
    ]) !!}
    <div class="ps-product__content">
        @if (is_plugin_active('marketplace') && $product->store->id)
            <p class="ps-product__vendor">
                <span>{{ __('Sold by') }}: </span>
                <a href="{{ $product->store->url }}" class="text-uppercase" title="{{ __('Visit store') }}: {{ $product->store->name }}">{{ $product->store->name }} {!! $product->store->badge !!}</a>
            </p>
        @endif
        <a class="ps-product__title" href="{{ $product->url }}" title="{{ $product->name }}">{!! BaseHelper::clean($product->name) !!}</a>
        @if (EcommerceHelper::isReviewEnabled())
            <div class="rating_wrap">
                <div class="rating">
                    <div class="product_rate" style="width: {{ $product->reviews_avg * 20 }}%"></div>
                </div>
                <span class="rating_num">({{ $product->reviews_count }})</span>
            </div>
        @endif
        <div class="ps-product__progress-bar ps-progress" data-value="{{ $product->pivot->quantity > 0 ? ($product->pivot->sold / $product->pivot->quantity) * 100 : 0 }}">
            <div class="ps-progress__value"><span style="width: {{ $product->pivot->quantity > 0 ? ($product->pivot->sold / $product->pivot->quantity) * 100 : 0 }}%"></span></div>
            @if (Botble\Ecommerce\Facades\FlashSale::isShowSaleCountLeft())
                @if ($product->pivot->quantity > $product->pivot->sold)
                    <p>{{ __('Sold') }}: {{ (int)$product->pivot->sold }}</p>
                @else
                    <p class="text-danger">{{ __('Sold out') }}</p>
                @endif
            @endif
        </div>
    </div>
</div>

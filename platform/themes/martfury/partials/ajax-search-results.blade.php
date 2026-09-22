@if ($products->isNotEmpty())
    <div class="ps-panel__content">
        @foreach($products as $product)
            <div class="ps-product ps-product--wide ps-product--search-result">
                <div class="ps-product__thumbnail">
                    <a href="{{ $product->url }}">
                        {!! RvMedia::image($product->image, $product->name, 'small') !!}
                    </a>
                </div>
                    <div class="ps-product__content">
                    <a class="ps-product__title" href="{{ $product->url }}">{!! BaseHelper::clean($product->name) !!}</a>
                    @if (EcommerceHelper::isReviewEnabled())
                        @if ($product->reviews_avg > 0)
                            <div class="rating_wrap">
                                <div class="rating">
                                    <div class="product_rate" style="width: {{ $product->reviews_avg * 20 }}%"></div>
                                </div>
                                <span class="rating_num">({{ $product->reviews_count }})</span>
                            </div>
                        @endif
                    @endif
                    {!! Theme::partial('product-price', [
                        'product' => $product,
                        'priceWrapperTag' => 'p',
                        'priceWrapperClass' => 'ps-product__price',
                    ]) !!}
                </div>
            </div>
        @endforeach
    </div>
    <div class="ps-panel__footer text-center"><a href="{{ route('public.products') }}?q={{ $query }}">{{ __('See all results') }}</a></div>
@else
    <div class="text-center">{{ __('No products found.') }}</a></div>
@endif

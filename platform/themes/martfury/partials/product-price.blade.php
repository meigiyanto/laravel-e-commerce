@php
    $price = $product->price();
    $priceValue = $price->getPrice();

    $priceFormatted ??= $price->displayAsText();
    $priceOriginalFormatted ??= $price->displayPriceOriginalAsText();

    $priceWrapperTag ??= 'p';
    $priceWrapperClass ??= 'ps-product__price';
    $priceClass ??= '';
    $priceOriginalWrapperClass ??= '';
    $priceOriginalClass ??= '';
    $salePercentageClass ??= '';

    $shouldShowPrice = (! EcommerceHelper::hideProductPrice() || EcommerceHelper::isCartEnabled())
        && (! EcommerceHelper::hideProductPriceWhenZero() || $priceValue > 0);
@endphp

@if ($shouldShowPrice)
    <{{ $priceWrapperTag }} class="{{ trim($priceWrapperClass . ($product->isOnSale() ? ' sale' : '')) }}">
        <span class="{{ trim($priceClass) }}" data-bb-value="product-price">{{ $priceFormatted }}</span>

        @if ($product->isOnSale())
            <span class="{{ trim($priceOriginalWrapperClass) }}">
                <small>
                    <del class="{{ trim($priceOriginalClass) }}" data-bb-value="product-original-price">
                        {{ $priceOriginalFormatted }}
                    </del>
                </small>
            </span>

            @if (! empty($showSalePercentage))
                <small class="{{ trim($salePercentageClass) }}">
                    ({{ get_sale_percentage($product->price, $product->front_sale_price) }})
                </small>
            @endif
        @endif
    </{{ $priceWrapperTag }}>
@endif

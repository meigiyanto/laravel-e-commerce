<section class="compare-area pt-50 pb-50">
    <div class="container">
        @if ($products->isNotEmpty())
            <div class="compare-table table-responsive text-center">
                <table class="table table-bordered">
                    <tbody>
                    <tr>
                        <th>{{ trans('plugins/ecommerce::products.product') }}</th>
                        @foreach ($products as $product)
                            <td>
                                <div class="compare-thumb">
                                    {{ RvMedia::image($product->image, $product->name, 'thumb') }}
                                    <h4 class="compare-product-title">
                                        <a href="{{ $product->url }}">{{ $product->name }}</a>
                                    </h4>

                                    <span @class(['text-danger' => $product->isOutOfStock(), 'text-success' => ! $product->isOutOfStock()])>
                                        @if ($product->isOutOfStock())
                                            ({{ trans('plugins/ecommerce::ecommerce.out_of_stock') }})
                                        @else
                                            ({{ trans('plugins/ecommerce::ecommerce.in_stock') }})
                                        @endif
                                    </span>
                                </div>
                            </td>
                        @endforeach
                    </tr>
                    <tr>
                        <th>{{ trans('plugins/ecommerce::products.description') }}</th>
                        @foreach ($products as $product)
                            <td>
                                <div class="compare-desc">
                                    {!! BaseHelper::clean($product->description) !!}
                                </div>
                            </td>
                        @endforeach
                    </tr>
                    <tr>
                        <th>{{ trans('plugins/ecommerce::products.price') }}</th>
                        @foreach ($products as $product)
                            <td>
                                @include(EcommerceHelper::viewPath('includes.product-price'), [
                                    'priceWrapperClassName' => 'compare-price',
                                    'priceClassName' => '',
                                    'priceOriginalWrapperClassName' => '',
                                    'priceOriginalClassName' => 'old-price',
                                ])
                            </td>
                        @endforeach
                    </tr>
                    <tr>
                        <th>{{ trans('plugins/ecommerce::products.sku') }}</th>
                        @foreach ($products as $product)
                            <td>{{ $product->sku ? '#' . $product->sku : '' }}</td>
                        @endforeach
                    </tr>
                    @foreach ($attributeSets as $attributeSet)
                        @continue(! $attributeSet->is_comparable)

                        <tr>
                            <th>{{ $attributeSet->title }}</th>

                            @foreach ($products as $product)
                                <td>
                                    {{ render_product_attributes_view_only($product, $attributeSet) }}
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                    <tr>
                        <th>{{ trans('plugins/ecommerce::ecommerce.add_to_cart_2') }}</th>
                        @foreach ($products as $product)
                            <td>
                                <div class="compare-add-to-cart d-flex justify-content-center">
                                    <button
                                        title="{{ trans('plugins/ecommerce::ecommerce.add_to_cart_1') }}"
                                        type="submit"
                                        class="btn btn-primary bb-btn-product-actions-icon"
                                        data-bb-toggle="add-to-cart"
                                        data-url="{{ route('public.cart.add-to-cart') }}"
                                        data-id="{{ $product->original_product->id }}"
                                        {!! EcommerceHelper::jsAttributes('add-to-cart', $product) !!}
                                    >
                                        <x-core::icon name="ti ti-shopping-cart"/>
                                        {{ trans('plugins/ecommerce::ecommerce.add_to_cart_1') }}
                                    </button>
                                </div>
                            </td>
                        @endforeach
                    </tr>
                    <tr>
                        <th>{{ trans('plugins/ecommerce::review.rating') }}</th>
                        @foreach ($products as $product)
                            <td>
                                @if (EcommerceHelper::isReviewEnabled() && (!EcommerceHelper::hideRatingWhenNoReviews() || $product->reviews_count > 0))
                                    <div class="compare-rating d-flex justify-content-center">
                                        @include(EcommerceHelper::viewPath('includes.rating-star'), ['avg' => $product->reviews_avg, 'size' => 80])
                                    </div>
                                @endif
                            </td>
                        @endforeach
                    </tr>
                    <tr>
                        <th>{{ trans('plugins/ecommerce::ecommerce.remove') }}</th>
                        @foreach ($products as $product)
                            <td>
                                <div class="compare-remove">
                                    <button class="btn btn-icon" data-bb-toggle="remove-from-compare" data-url="{{ route('public.compare.remove', $product->id) }}">
                                        <x-core::icon name="ti ti-trash" />
                                    </button>
                                </div>
                            </td>
                        @endforeach
                    </tr>
                    </tbody>
                </table>
            </div>
        @else
            @include(EcommerceHelper::viewPath('includes.empty-state'), ['title' => trans('plugins/ecommerce::ecommerce.your_compare_list_is_empty')])
        @endif
    </div>
</section>

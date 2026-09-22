@php
    $title = $title ?? trans('plugins/ecommerce::ecommerce.your_cart_is_empty');
    $description = $description ?? trans('plugins/ecommerce::review.explore_and_add_items_to_get_started');
    $route = $route ?? route('public.products');
    $label = $label ?? trans('plugins/ecommerce::review.start_shopping');
@endphp

<div class="text-center pt-50 bb-empty-state">
    <h3 class="mb-3">{!! BaseHelper::clean($title) !!}</h3>
    <p class="mb-3">{!! BaseHelper::clean($description) !!}</p>
    <a href="{{ $route }}" class="btn btn-outline-primary">{!! BaseHelper::clean($label) !!}</a>
</div>

<div class="ps-page--shop">
    <div class="ps-layout--shop">
        <div class="ps-layout__left">
            <div class="screen-darken"></div>
            <div class="ps-layout__left-container">
                <div class="ps-filter__header d-block d-xl-none">
                    <h3>{{ __('Filter Products') }}</h3><a class="ps-btn--close ps-btn--no-border" href="#"></a>
                </div>
                <div class="ps-layout__left-content">
                    @include(Theme::getThemeNamespace() . '::views.ecommerce.includes.filters')
                </div>
            </div>
        </div>
        <div class="ps-layout__right">
            <div class="ps-block--shop-features">
                <div class="ps-block__header">
                    <h1 class="h1">{{ $brand->name }}</h1>
                </div>
                @if ($brand->description)
                    <div class="ps-block__content">
                        <div class="ps-section__content">
                            {!! BaseHelper::clean($brand->description) !!}
                        </div>
                    </div>
                @endif
            </div>
            @include(Theme::getThemeNamespace('views.ecommerce.includes.products-list'))
        </div>
    </div>
</div>

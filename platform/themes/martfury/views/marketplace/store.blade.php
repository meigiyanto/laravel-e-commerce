<div class="ps-page--single ps-page--vendor">
    <section class="ps-store-list">
        <div class="container">
            @php $coverImage = $store->getMetaData('cover_image', true); @endphp
            <aside class="ps-block--store-banner" @if ($coverImage) style="background-image: url({{ RvMedia::getImageUrl($coverImage) }}); background-repeat: no-repeat;
                background-size: cover;
                background-position: center;background-color: #2f2f2f;" @else style="background-color: #2f2f2f;" @endif>
                <div class="ps-block__user" @if ($coverImage) style="background: rgba(0, 0, 0, 0.3)" @endif>
                    <div class="ps-block__user-avatar">
                        <img src="{{ $store->logo_url }}" alt="{{ $store->name }}">
                        @if (EcommerceHelper::isReviewEnabled())
                            <div class="rating_wrap">
                                <div class="rating">
                                    <div class="product_rate" style="width: {{ $store->reviews()->avg('star') * 20 }}%"></div>
                                </div>
                                <span class="rating_num">({{ $store->reviews()->count() }})</span>
                            </div>
                        @endif
                    </div>
                    <div class="ps-block__user-content">
                        <h3 class="text-white">{{ $store->name }} {!! $store->badge !!}</h3>
                        @if (! MarketplaceHelper::hideStoreAddress() && $store->full_address)
                            <p><i class="icon-map-marker" @if ($coverImage) style="color: #fff" @endif></i>&nbsp;{{ $store->full_address }}</p>
                        @endif
                        @if (!MarketplaceHelper::hideStorePhoneNumber() && $store->phone)
                            <p><i class="icon-telephone" @if ($coverImage) style="color: #fff" @endif></i>&nbsp;{{ $store->phone }}</p>
                        @endif
                        @if (!MarketplaceHelper::hideStoreEmail() && $store->email)
                            <p><i class="icon-envelope" @if ($coverImage) style="color: #fff" @endif></i>&nbsp;<a href="mailto:{{ $store->email }}">{{ $store->email }}</a></p>
                        @endif
                        @if (!MarketplaceHelper::hideStoreSocialLinks() && ($socials = $store->getMetaData('social_links', true)))
                            <ul class="ps-block__user-social mt-3">
                                @foreach (MarketplaceHelper::getAllowedSocialLinks() as $key => $social)
                                    @continue(! Arr::get($socials, $key))
                                    <li>
                                        <a href="{{ Arr::get($social, 'url') . Arr::get($socials, $key) }}" target="_blank" title="{{ Arr::get($social, 'title') }}">
                                            <x-core::icon :name="'ti ti-brand-' . Arr::get($social, 'icon')" />
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                </div>

                @php
                    $description = BaseHelper::clean($store->description);
                    $content = BaseHelper::clean($store->content);
                @endphp

                @if ($description || $content)
                    <div class="py-4 mb-4 bg-light">
                        <div class="px-4">
                            @if ($content)
                                <div
                                    id="store-content"
                                    class="d-none"
                                >
                                    {!! $content !!}
                                </div>
                            @endif

                            <div id="store-short-description">
                                {!! $description ?: Str::limit($content, 250) !!}
                            </div>

                            <a
                                class="text-link toggle-show-more ms-1"
                                href="#"
                            >{{ __('show more') }}</a>
                            <a
                                class="text-link toggle-show-less ms-1 d-none"
                                href="#"
                            >{{ __('show less') }}</a>
                        </div>
                    </div>
                @endif
            </aside>
            <div class="ps-section__wrapper">
                @if ($canContactStore = (MarketplaceHelper::isEnabledMessagingSystem() && (! auth('customer')->check() || $store->id != auth('customer')->user()->store->id)))
                    <div class="ps-layout--shop">
                        <div class="ps-layout__left">
                            <div class="store-contact-form mb-4 bg-light p-4">
                                <h3 class="fs-4">{{ __('Email :store', ['store' => $store->name]) }}</h3>
                                <p>{{ __('All messages are recorded and spam is not tolerated. Your email address will be shown to the recipient.') }}</p>
                                {!!
                                    $contactForm
                                    ->setFormOption('class', 'ps-form--contact-us contact-form bb-contact-store-form')
                                    ->setFormInputClass('form-control')
                                    ->setFormLabelClass('d-none visually-hidden')
                                    ->modify(
                                        'submit',
                                        'submit',
                                        Botble\Base\Forms\FieldOptions\ButtonFieldOption::make()
                                            ->addAttribute('data-bb-loading', 'button-loading')
                                            ->cssClass('ps-btn')
                                            ->label(__('Send message'))
                                            ->wrapperAttributes(['class' => 'mb-3 submit'])
                                            ->toArray(),
                                        true
                                    )
                                    ->renderForm()
                                !!}
                        </div>

                        @include(MarketplaceHelper::viewPath('includes.contact-form-script'))
                </div>
                <div class="ps-layout__right">
                    @endif
                    <div class="ps-shopping ps-tab-root">
                            <div class="ps-section__search">
                                <div class="mb-3">
                                    <form
                                        class="products-filter-form-vendor"
                                        action="{{ URL::current() }}"
                                        method="GET"
                                    >
                                        <div class="mb-5">
                                            <button><i class="icon-magnifier"></i></button>
                                            <input class="form-control" name="q" value="{{ BaseHelper::stringify(request()->query('q')) }}" type="text" placeholder="{{ __('Search in this store...') }}">
                                        </div>
                                    </form>
                                </div>
                            </div>
                            <div class="ps-shopping__header">
                                <p><strong> {{ $products->total() }}</strong> {{ __('Products found') }}</p>
                                <div class="ps-shopping__actions">
                                    <div class="ps-shopping__view">
                                        <p>{{ __('View') }}</p>
                                        <ul class="ps-tab-list">
                                            <li class="active"><a href="#tab-1"><i class="icon-grid"></i></a></li>
                                            <li><a href="#tab-2"><i class="icon-list4"></i></a></li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        <div class="ps-tabs">
                            <div class="ps-tab active" id="tab-1">
                                <div class="ps-shopping-product">
                                    <div class="row">
                                        @if ($products->isNotEmpty())
                                            @foreach($products as $product)
                                                <div class="col-xl-3 col-lg-4 col-md-4 col-sm-6 col-6 ">
                                                    <div class="ps-product">
                                                        {!! Theme::partial('product-item', ['product' => $product, 'lazy' => false]) !!}
                                                    </div>
                                                </div>
                                            @endforeach
                                        @endif
                                    </div>
                                </div>
                                <div class="ps-pagination">
                                    {!! $products->withQueryString()->links() !!}
                                </div>
                            </div>
                            <div class="ps-tab" id="tab-2">
                                <div class="ps-shopping-product">
                                    @if ($products->isNotEmpty())
                                        @foreach($products as $product)
                                            <div class="ps-product ps-product--wide">
                                                {!! Theme::partial('product-item-grid', ['product' => $product, 'lazy' => false]) !!}
                                            </div>
                                        @endforeach
                                    @endif
                                </div>
                                <div class="ps-pagination">
                                    {!! $products->withQueryString()->links() !!}
                                </div>
                            </div>
                        </div>
                    </div>
                    </div>
                        @if ($canContactStore)
                </div>
                    @endif
            </div>
        </div>
    </section>
</div>

<div class="row">
    @foreach($stores as $store)
        <div class="col-xl-4 col-lg-4 col-md-6 col-sm-6 col-12 ">
            <article class="ps-block--store-2">
                <div class="ps-block__content bg--cover" data-background="{{ asset('vendor/core/plugins/marketplace/img/default-store-banner.png') }}">
                    <figure>
                        <h4>
			     {{ $store->name }}
                             {!! $store->badge !!}
                        </h4>
                        @if (EcommerceHelper::isReviewEnabled())
                            <div class="rating_wrap">
                                <div class="rating">
                                    <div class="product_rate" style="width: {{ $store->reviews->avg('star') * 20 }}%"></div>
                                </div>
                                <span class="rating_num">({{ $store->reviews->count() }})</span>
                            </div>
                        @endif
                        @if(! MarketplaceHelper::hideStoreAddress() && $store->full_address)
                            <p>{{ $store->full_address }}</p>
                        @endif
                        @if (!MarketplaceHelper::hideStorePhoneNumber() && $store->phone)
                            <p><i class="icon-telephone"></i><span>&nbsp;{{ $store->phone }}</span></p>
                        @endif
                        @if (!MarketplaceHelper::hideStoreEmail() && $store->email)
                            <p><i class="icon-envelope"></i>&nbsp;<a href="mailto:{{ $store->email }}">{{ $store->email }}</a></p>
                        @endif
                        @if (!MarketplaceHelper::hideStoreSocialLinks() && ($socials = $store->getMetaData('social_links', true)))
                            <ul class="ps-block__social mt-2">
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
                    </figure>
                </div>
                <div class="ps-block__author">
                    <a class="ps-block__user" href="{{ $store->url }}">
                        {!! RvMedia::image($store->logo, $store->name, 'small') !!}
                    </a>
                    <a class="ps-btn" href="{{ $store->url }}">{{ __('Visit Store') }}</a>
                </div>
            </article>
        </div>
@endforeach

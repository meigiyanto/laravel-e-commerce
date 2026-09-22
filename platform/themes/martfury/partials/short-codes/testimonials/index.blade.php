<div {!! $shortcode->htmlAttributes() !!} class="widget-testimonials mt-40 mb-40">
    <div class="ps-container">
        @if($shortcode->title || $shortcode->subtitle)
            <div class="ps-section__header">
                <h3>{{ $shortcode->title }}</h3>
                @if ($shortcode->subtitle)
                    <p>{{ $shortcode->subtitle }}</p>
                @endif
            </div>
        @endif
        <div class="ps-section__content">
            <div
                class="ps-carousel--nav owl-slider"
                data-owl-auto="{{ $shortcode->is_autoplay == 'yes' ? 'true' : 'false' }}"
                data-owl-loop="{{ ($shortcode->infinite == 'yes' || $shortcode->is_infinite == 'yes') ? 'true' : 'false' }}"
                data-owl-speed="{{ in_array($shortcode->autoplay_speed, theme_get_autoplay_speed_options()) ? $shortcode->autoplay_speed : 3000 }}"
                data-owl-gap="30"
                data-owl-nav="true"
                data-owl-dots="true"
                data-owl-item="{{ $shortcode->slides_to_show ?: 3 }}"
                data-owl-item-xs="1"
                data-owl-item-sm="1"
                data-owl-item-md="2"
                data-owl-item-lg="2"
                data-owl-item-xl="{{ min($shortcode->slides_to_show ?: 3, 3) }}"
                data-owl-duration="1000"
                data-owl-mousedrag="on"
            >
                @foreach($testimonials as $testimonial)
                    <div class="testimonial-item">
                        <div class="testimonial-item-body grey-bg-7 rounded h-100">
                            <div class="testimonial-quote">
                                <img src="{{ Theme::asset()->url('img/testimonial-quote.png') }}" alt="quote" />
                            </div>
                            <div class="testimonial-rating @if ($shortcode->filled_color === 'yes') text-warning @endif">
                                @php
                                    $stars = $testimonial->shortcode_stars ?? 5;
                                @endphp
                                @for ($i = 1; $i <= 5; $i++)
                                    <span><x-core::icon name="{{ $i <= $stars ? 'ti ti-star-filled' : 'ti ti-star' }}" /></span>
                                @endfor
                            </div>
                            <div class="testimonial-content">
                                <p>
                                    {!! BaseHelper::clean($testimonial->content) !!}
                                </p>
                            </div>
                            <div class="testimonial-user d-flex align-items-center">
                                <div class="testimonial-avatar me-3">
                                    {{ RvMedia::image($testimonial->image, $testimonial->name, 'thumb') }}
                                </div>
                                <div class="testimonial-user-info">
                                    <h6>{{ $testimonial->name }}</h6>
                                    <span>{{ $testimonial->company }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

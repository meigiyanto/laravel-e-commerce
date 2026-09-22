@php
    Theme::layout('blog-sidebar')
@endphp

<div class="ps-post--detail sidebar">
    <div class="ps-post__header">
        <p>{{ Theme::formatDate($post->created_at) }} @if ($post->author) / {{ __('By') }} {{ $post->author->name }} @endif / {{ __('in') }} @foreach($post->categories as $category) <a href="{{ $category->url }}">{!! BaseHelper::clean($category->name) !!}</a> @endforeach</p>
    </div>
    <div class="ps-post__content" style="padding-top: 0;">
        <div class="ck-content">{!! BaseHelper::clean($post->content) !!}</div>
            <br />
            {!! apply_filters(BASE_FILTER_PUBLIC_COMMENT_AREA, null, $post) !!}
    </div>
    <div class="ps-post__footer">
        @if (!$post->tags->isEmpty())
            <p class="ps-post__tags">{{ __('Tags') }}:
                @foreach ($post->tags as $tag)
                    <a href="{{ $tag->url }}">{{ $tag->name }}</a>
                @endforeach
            </p>
        @endif
        <div class="ps-post__sharing">
            <span>{{ __('Share:') }}</span>

            {!! Theme::renderSocialSharing($post->url, SeoHelper::getDescription(), $post->image) !!}
        </div>
    </div>
    @php $relatedPosts = get_related_posts($post->id, 2); @endphp

    @if ($relatedPosts->count())
        <div class="ps-related-posts">
            <h3>{{ __('Related Posts') }}</h3>
            <div class="row">
                @foreach ($relatedPosts as $post)
                    <div class="col-sm-6 col-12">
                        <div class="ps-post">
                            <div class="ps-post__thumbnail">
                                <a class="ps-post__overlay" href="{{ $post->url }}"></a>
                                {!! RvMedia::image($post->image, $post->name, 'small') !!}
                            </div>
                            <div class="ps-post__content" style="padding: 20px 0;">
                                <div class="ps-post__top">
                                    <div class="ps-post__meta">
                                        @foreach($post->categories as $category)
                                            <a href="{{ $category->url }}">{!! BaseHelper::clean($category->name) !!}</a>
                                        @endforeach
                                    </div>
                                    <a class="ps-post__title" href="{{ $post->url }}">{!! BaseHelper::clean($post->name) !!}</a>
                                </div>
                                <div class="ps-post__bottom">
                                    <p>{{ Theme::formatDate($post->created_at) }} @if ($post->author) {{ __('by') }} {{ $post->author->name }} @endif</p>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>



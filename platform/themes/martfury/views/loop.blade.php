@foreach ($posts as $post)
    @if ($loop->first)
        <div class="ps-post">
            <div class="ps-post__thumbnail">
                <a class="ps-post__overlay" href="{{ $post->url }}"></a>
                {!! RvMedia::image($post->image, $post->name, 'medium') !!}
            </div>
            <div class="ps-post__content">
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
    @endif
@endforeach

@if ($posts->count() > 1)
    <div class="row">
        @foreach ($posts as $post)
            @if (!$loop->first)
                <div class="col-xl-6 col-lg-6 col-md-6 col-sm-6 col-12 ">
                    <div class="ps-post">
                        <div class="ps-post__thumbnail">
                            <a class="ps-post__overlay" href="{{ $post->url }}"></a>
                            {!! RvMedia::image($post->image, $post->name, 'small') !!}
                        </div>
                        <div class="ps-post__content">
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
            @endif
        @endforeach
    </div>
@endif

<div class="ps-pagination">
    {!! $posts->withQueryString()->links() !!}
</div>

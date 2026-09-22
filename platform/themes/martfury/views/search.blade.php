@php
    Theme::layout('blog-sidebar')
@endphp

@if ($posts->isNotEmpty())
    @include(Theme::getThemeNamespace() . '::views.loop', compact('posts'))
@endif

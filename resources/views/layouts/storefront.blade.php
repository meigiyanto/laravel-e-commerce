<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"><head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <title>@yield('title', config('app.name'))</title>
    @vite(['resources/css/app.css', 'resources/css/storefront.css', 'resources/js/app.js', 'resources/js/storefront.js'])
    @stack('styles')
    {{-- Development helper --}}
    @if (app()->environment('local'))
        <script src="https://cdn.jsdelivr.net/npm/eruda@3.4.3/eruda.min.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                if (typeof eruda !== 'undefined') {
                    eruda.init();
                }
            });
        </script>
    @endif
</head>

<body>
    @include('layouts.partials.storefront.topbar')
    @include('layouts.partials.storefront.header')
    @include('layouts.partials.storefront.navbar')
    @include('layouts.partials.storefront.navbar-mobile-top')

    {{-- ====================================================         
    CONTENT
    ==================================================== --}}
    <main class="store-page">
        @yield('content')
    </main>

    @include('layouts.partials.storefront.footer')
    {{-- Back to Top --}}
    <button
        type="button"
        id="storeBackToTop"
        class="store-back-to-top"
        aria-label="Kembali ke atas"
        title="Kembali ke atas"
    >
        <i class="bi bi-arrow-up" aria-hidden="true"></i>
    </button>
    @include('layouts.partials.storefront.navbar-mobile-bottom')
    @stack('scripts')
</body>
</html>

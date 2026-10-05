<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"><head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'MeiStore')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
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

    @include('layouts.partials.storefront.navbar-mobile')

    {{-- ====================================================         CONTENT
    ==================================================== --}}
    <main class="store-page">
        @yield('content')
    </main>

    @include('layouts.partials.storefront.footer')

    @include('layouts.partials.storefront.navbar-bottom')

    {{-- Bootstrap JS --}}
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>

    @stack('scripts')
</body>
</html>

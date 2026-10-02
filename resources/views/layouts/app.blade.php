<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <title>{{ config('app.name', 'Laravel E-Commerce Store') }}</title>
        <meta name="csrf-token" content="{{ csrf_token() }}" />
        <link rel="shortcut icon" href="{{ asset('images/favicon.png') }}" />
        
        <link rel="stylesheet" href="{{ asset('fonts/DMSans-Medium.woff2') }}" />
        <link rel="stylesheet" href="{{ asset('css/libs/fontawesome-icons.css') }}" />
        <link rel="stylesheet" href="{{ asset('css/libs/bootstrap-icons.css') }}" />
        <link rel="stylesheet" href="{{ asset('css/libs/themify-icons.css') }}" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <link id="skin-default" rel="stylesheet" href="{{ asset('css/theme.css') }}" />

        {{-- Eruda hanya untuk development --}}
        @if(app()->environment('local'))
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
    <body class="nk-body bg-lighter npc-general has-sidebar ui-shady">
        <div class="nk-app-root">
            <div class="nk-main">
                @include('layouts.partials.aside')
                <div class="nk-wrap">
                    @include('layouts.partials.header')

                    <div class="nk-content ">
                        <div class="container-fluid">
                            <div class="nk-content-inner">
                                <div class="nk-content-body">
                                    @yield('content')
                                </div>
                            </div>
                        </div>
                    </div>

                    @include('layouts.partials.footer')
                </div>
            </div>
        </div>
    </body>

</html>

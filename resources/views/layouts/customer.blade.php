<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    <meta
        name="csrf-token"
        content="{{ csrf_token() }}"
    >

    <title>
        @yield('title', 'Dashboard') | {{ config('app.name', 'MeiStore') }}
    </title>

    <link
        rel="shortcut icon"
        href="{{ asset('images/favicon.png') }}"
    >

    @vite([
        'resources/css/admin.css',
        'resources/js/admin.js'
    ])

    @stack('styles')
</head>

<body class="nk-body bg-lighter npc-general">

    <div class="nk-app-root">

        <div class="nk-main">

            <div class="nk-wrap">

                @include('layouts.partials.header')

                <div class="nk-content">

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

    @stack('scripts')

</body>

</html>

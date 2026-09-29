<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>{{ config('app.name', 'Laravel E-Commerce Store') }}</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <!-- Fav Icon  -->
    <link rel="shortcut icon" href="./images/favicon.png">
    <!-- Font -->
    <link rel="stylesheet" href={{ asset('fonts/DMSans-Medium.woff2&display=swap')}}/>

    @vite(['resources/css/app.css', 'resources/js/bundle.js', 'resources/js/main.js'])
    <link id="skin-default" rel="stylesheet" href={{ asset('css/theme.css') }} />
    <script src="https://cdn.jsdelivr.net/npm/eruda@3.4.3/eruda.min.js"></script>
    <script>eruda.init()</script>
</head>

<body class="nk-body bg-lighter npc-general has-sidebar ui-shady ">

    <div class="nk-app-root">
        <div class="nk-main">
            @include('layouts.partials.aside')

            <div class="nk-wrap">
                @include('layouts.partials.header')
                @yield('content')
                @include('layouts.partials.footer')
            </div>

        </div>
    </div>

</body>
</html>

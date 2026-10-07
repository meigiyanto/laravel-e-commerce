<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') | {{ config('app.name', 'MeiStore') }}
    </title>

    <link rel="shortcut icon" href="{{ asset('images/favicon.png') }}">
    @vite(['resources/css/dashboard.css', 'resources/js/dashboard.js'])
    @stack('styles')
</head>
<body>
    @include('layouts.partials.dashboard.header')
    @yield('content')
    @include('layouts.partials.dashboard.footer')
    @stack('scripts')
</body>
</html>

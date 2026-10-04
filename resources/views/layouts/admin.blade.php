<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin Dashboard') - {{ config('app.name', 'Laravel E-Commerce Store') }}</title>
    <link rel="shortcut icon" href="{{ asset('images/favicon.ico') }}">
    @vite(['resources/css/admin.css', 'resources/js/admin.js'])
</head>
<body class="nk-body bg-lighter npc-general has-sidebar ui-shady">
    <div class="nk-app-root">
        <div class="nk-main">
            @include('layouts.partials.aside')
            <div class="nk-wrap">
                @include('layouts.partials.header')
                <div class="nk-content">
                    <div class="container-fluid">
                        <div class="nk-content-inner">
                            <div class="nk-content-body">
                                @if (session('success'))
                                    <div class="alert alert-pro alert-success alert-dismissible">
                                        <div class="alert-text">{{ session('success') }}</div>
                                        <button type="button" class="close" data-bs-dismiss="alert" aria-label="Close"><em class="icon ni ni-cross"></em></button>
                                    </div>
                                @endif
                                @if (session('error'))
                                    <div class="alert alert-pro alert-danger alert-dismissible">
                                        <div class="alert-text">{{ session('error') }}</div>
                                        <button type="button" class="close" data-bs-dismiss="alert" aria-label="Close"><em class="icon ni ni-cross"></em></button>
                                    </div>
                                @endif
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

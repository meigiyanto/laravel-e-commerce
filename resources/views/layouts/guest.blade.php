<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>{{ config('app.name', 'Laravel E-Commerce Store') }}</title>

    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>

<body class="auth-body">

    <div class="auth-container">

        <a href="{{ url('/') }}" class="auth-logo">
            E-Commerce Store
        </a>

        <div class="auth-card">
            {{ $slot }}
        </div>

    </div>

</body>
</html>

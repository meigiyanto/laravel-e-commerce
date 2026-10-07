<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name', 'E-Commerce Store'))</title>
    <link rel="shortcut icon" href="{{ asset('images/favicon.png') }}">
    @vite('resources/css/app.css')
    <link rel="stylesheet" href="{{ asset('css/auth.css') }}">
</head>
<body class="auth-page">
    <main class="auth-shell">
        <section class="auth-panel auth-panel-form">
            <div class="auth-form-inner">
                <div class="text-center">
                    <a href="{{ url('/') }}" class="auth-brand">
                        <span class="auth-brand-mark">E</span>
                        <span>E-Commerce Store</span>
                    </a>
                </div>

                @yield('content')

                <p class="auth-copyright">&copy; {{ date('Y') }} E-Commerce Store. All rights reserved.</p>
            </div>
        </section>

        <aside class="auth-panel auth-panel-promo">
            <div class="auth-promo">
                <span class="auth-promo-badge">ONLINE STORE</span>
                <h2>Belanja lebih mudah, cepat, dan nyaman.</h2>
                <p>
                    Kelola akun, temukan produk favorit,
                    dan nikmati pengalaman berbelanja
                    yang sederhana dalam satu tempat.
                </p>

                <div class="auth-promo-list">
                    <div class="auth-promo-item">
                        <span class="auth-promo-icon">✓</span>
                        <div>
                            <strong>Produk pilihan</strong>
                            <small>
                                Temukan produk yang sesuai
                                kebutuhan Anda.
                            </small>
                        </div>
                    </div>

                    <div class="auth-promo-item">
                        <span class="auth-promo-icon">✓</span>
                        <div>
                            <strong>Checkout mudah</strong>

                            <small>
                                Proses pesanan dengan alur
                                yang sederhana.
                            </small>
                        </div>
                    </div>
                    <div class="auth-promo-item">
                        <span class="auth-promo-icon">✓</span>
                        <div>
                            <strong>Akun aman</strong>
                            <small>
                                Data akun dikelola melalui
                                autentikasi Laravel.
                            </small>
                        </div>
                    </div>

                </div>
            </div>
        </aside>
    </main>
</body>
</html>

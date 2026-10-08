@extends('layouts.storefront')

@section('title', config('app.name') . ' - My Account')

@section('content')

{{-- =========================================================
    BREADCRUMB
========================================================= --}}
<div class="store-breadcrumb">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item">
                    <a href="{{ route('storefront.home') }}">
                        <i class="bi bi-house me-1"></i>
                        Home
                    </a>
                </li>

                <li class="breadcrumb-item active" aria-current="page">
                    My Account
                </li>
            </ol>
        </nav>
    </div>
</div>


{{-- =========================================================
    ACCOUNT HEADER
========================================================= --}}
<section class="store-section pb-3">
    <div class="container">
        <div class="store-section-header">
            <div>
                <span class="store-section-eyebrow">
                    Account
                </span>

                <h1 class="store-section-title">
                    My Account
                </h1>

                <p class="store-section-subtitle">
                    Selamat datang kembali,
                    {{ Auth::user()->name }}.
                </p>
            </div>
        </div>
    </div>
</section>


{{-- =========================================================
    ACCOUNT OVERVIEW
========================================================= --}}
<section class="pb-5">
    <div class="container">

        <div class="row g-4">

            {{-- =================================================
                PROFILE
            ================================================= --}}
            <div class="col-md-6 col-xl-4">
                <div class="store-account-card h-100">

                    <div class="store-account-card-icon">
                        <i class="bi bi-person-circle"></i>
                    </div>

                    <div class="store-account-card-body">

                        <h2 class="store-account-card-title">
                            Profile
                        </h2>

                        <p class="store-account-card-text">
                            Kelola informasi pribadi dan keamanan
                            akun Anda.
                        </p>

                        <a
                            href="{{ route('profile.edit') }}"
                            class="store-account-card-link"
                        >
                            Edit Profile
                            <i class="bi bi-arrow-right"></i>
                        </a>

                    </div>

                </div>
            </div>


            {{-- =================================================
                ORDERS
            ================================================= --}}
            <div class="col-md-6 col-xl-4">
                <div class="store-account-card h-100">

                    <div class="store-account-card-icon">
                        <i class="bi bi-bag"></i>
                    </div>

                    <div class="store-account-card-body">

                        <h2 class="store-account-card-title">
                            My Orders
                        </h2>

                        <p class="store-account-card-text">
                            Lihat riwayat pesanan dan detail transaksi
                            Anda.
                        </p>

                        <a
                            href="{{ route('orders.index') }}"
                            class="store-account-card-link"
                        >
                            View Orders
                            <i class="bi bi-arrow-right"></i>
                        </a>

                    </div>

                </div>
            </div>


            {{-- =================================================
                STORE
            ================================================= --}}
            <div class="col-md-6 col-xl-4">
                <div class="store-account-card h-100">

                    <div class="store-account-card-icon">
                        <i class="bi bi-shop"></i>
                    </div>

                    <div class="store-account-card-body">

                        <h2 class="store-account-card-title">
                            Continue Shopping
                        </h2>

                        <p class="store-account-card-text">
                            Jelajahi produk dan temukan sesuatu yang
                            Anda sukai.
                        </p>

                        <a
                            href="{{ route('storefront.shop') }}"
                            class="store-account-card-link"
                        >
                            Visit Store
                            <i class="bi bi-arrow-right"></i>
                        </a>

                    </div>

                </div>
            </div>

        </div>

    </div>
</section>

@endsection

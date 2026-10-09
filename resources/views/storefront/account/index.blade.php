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
                <span class="store-section-eyebrow">Account</span>
                <h1 class="store-section-title">Account Overview</h1>
                <p class="store-section-subtitle">
                    Welcome back, <strong>{{ Auth::user()->name }}.</strong>
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
            <div class="col-md-6 col-xl-4">
                <div class="store-profile-sidebar">
                    <div class="store-profile-sidebar-item">
                        <div class="store-profile-sidebar-icon">
                            <i class="bi bi-person-circle"></i>
                        </div>

                        <div>
                            <strong>{{ Auth::user()->name }}</strong>
                            <span>{{ Auth::user()->email }}</span>
                        </div>
                    </div>

                    <div class="store-profile-sidebar-divider"></div>
                    <a
                        href="{{ route('account.index') }}"
                        class="store-profile-sidebar-link {{ request()->routeIs('account.index') ? 'active' : '' }}"
                    >
                        <i class="bi bi-grid"></i>
                        <span>Account</span>
                        <i class="bi bi-chevron-right"></i>
                    </a>
                    <a
                        href="{{ route('profile.edit') }}"
                        class="store-profile-sidebar-link"
                    >
                        <i class="bi bi-person"></i>
                        <span>Manage Account Settings</span>
                        <i class="bi bi-chevron-right"></i>
                    </a>
                    <a
                        href="{{ route('orders.index') }}"
                        class="store-profile-sidebar-link"
                    >
                        <i class="bi bi-bag"></i>
                        <span>Orders</span>
                        <i class="bi bi-chevron-right"></i>
                    </a>
                    <a
                        href="{{ route('orders.index') }}"
                        class="store-profile-sidebar-link"
                    >
                        <i class="bi bi-receipt"></i>
                        <span>Invoices</span>
                        <i class="bi bi-chevron-right"></i>
                    </a>
                    <a
                        href="#"
                        class="store-profile-sidebar-link"
                    >
                        <i class="bi bi-yelp"></i>
                        <span>Reviews</span>
                        <i class="bi bi-chevron-right"></i>
                    </a>
                    <a
                        href="#"
                        class="store-profile-sidebar-link"
                    >
                        <i class="bi bi-arrow-return-left"></i>
                        <span>Refund</span>
                        <i class="bi bi-chevron-right"></i>
                    </a>
                    <a
                        href="#"
                        class="store-profile-sidebar-link"
                    >
                        <i class="bi bi-cloud-download"></i>
                        <span>Downloads</span>
                        <i class="bi bi-chevron-right"></i>
                    </a>
                    <a
                        href="#"
                        class="store-profile-sidebar-link"
                    >
                        <i class="bi bi-person"></i>
                        <span>Address</span>
                        <i class="bi bi-chevron-right"></i>
                    </a>
                </div>
            </div>

            {{-- =================================================
                PROFILE
            ================================================= --}}
            <div class="col-md-6 col-xl-4">
                <div class="store-account-card h-100">

                    <div class="store-account-card-icon">
                        <i class="bi bi-person-circle"></i>
                    </div>

                    <div class="store-account-card-body">
                        <h2 class="store-account-card-title">Profile</h2>
                        <p class="store-account-card-text">Manage your personal information and security account.</p>
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
                            View your history orders and detail transaction
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
                Address
            ================================================= --}}
            <div class="col-md-6 col-xl-4">
                <div class="store-account-card h-100">

                    <div class="store-account-card-icon">
                        <i class="bi bi-geo-alt-fill"></i>
                    </div>

                    <div class="store-account-card-body">

                        <h2 class="store-account-card-title">
                            Address
                        </h2>

                        <p class="store-account-card-text"> Manage order delivery destination addresses.</p>

                        <a
                            href="#"
                            class="store-account-card-link"
                        >
                            Manage Address
                            <i class="bi bi-arrow-right"></i>
                        </a>

                    </div>

                </div>
            </div>

        </div>

    </div>
</section>

@endsection

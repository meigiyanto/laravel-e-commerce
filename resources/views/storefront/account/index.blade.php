@extends('layouts.storefront')

@section('title', config('app.name') . ' - Account')

@section('content')
<div class="store-breadcrumb">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item">
                    <a href="{{ route('storefront.home') }}">
                        <i class="bi bi-house me-1"></i> Home
                    </a>
                </li>
                <li class="breadcrumb-item">Account</li>
                <li class="breadcrumb-item active" aria-current="page">
                    Account Overview
                </li>
            </ol>
        </nav>
    </div>
</div>

<section class="store-section pb-3">
    <div class="container">
        <div class="store-section-header">
            <div>
                <span class="store-section-eyebrow">Account</span>
                <h1 class="store-section-title">Account Overview</h1>
                <p class="store-section-subtitle">
                    Welcome back, <strong>{{ $user->name }}</strong>.
                    Manage your account and orders here.
                </p>
            </div>
        </div>
    </div>
</section>

<section class="pb-5">
    <div class="container">
        <div class="row g-4">
            {{-- Sidebar --}}
            <div class="col-lg-4">
                @include('components.account.sidebar', ['user' => $user])
            </div>

            {{-- Main Content --}}
            <div class="col-lg-8">
                {{-- Account Statistics --}}
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <div class="store-account-card h-100">
                            <div class="store-account-card-icon">
                                <i class="bi bi-bag"></i>
                            </div>
                            <div class="store-account-card-body">
                                <h2 class="store-account-card-title">
                                    My Orders
                                </h2>
                                <p class="store-account-card-text">
                                    {{ $user->orders_count }} total orders
                                </p>
                                <a href="{{ route('orders.index') }}"
                                    class="store-account-card-link">
                                    View Orders
                                    <i class="bi bi-arrow-right"></i>
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="store-account-card h-100">
                            <div class="store-account-card-icon">
                                <i class="bi bi-geo-alt"></i>
                            </div>
                            <div class="store-account-card-body">
                                <h2 class="store-account-card-title">
                                    My Addresses
                                </h2>
                                <p class="store-account-card-text">
                                    {{ $user->addresses_count }} saved addresses
                                </p>
                                <a href="{{ route('account.addresses.index') }}"
                                    class="store-account-card-link">
                                    Manage Addresses
                                    <i class="bi bi-arrow-right"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Personal Information --}}
                <div class="store-account-card mb-4">
                    <div class="store-account-card-icon">
                        <i class="bi bi-person-circle"></i>
                    </div>
                    <div class="store-account-card-body">
                        <h2 class="store-account-card-title">
                            Personal Information
                        </h2>
                        <p class="store-account-card-text">
                            {{ $user->name }}<br>
                            {{ $user->email }}<br>
                            {{ $user->phone ?: 'Phone number not provided' }}
                        </p>
                        <a href="{{ route('profile.edit') }}"
                            class="store-account-card-link">
                            Edit Profile
                            <i class="bi bi-arrow-right"></i>
                        </a>
                    </div>
                </div>

                {{-- Default Address --}}
                <div class="store-account-card mb-4">
                    <div class="store-account-card-icon">
                        <i class="bi bi-house-door"></i>
                    </div>
                    <div class="store-account-card-body">
                        <h2 class="store-account-card-title">
                            Default Shipping Address
                        </h2>

                        @if ($user->defaultAddress)
                            <p class="store-account-card-text">
                                <strong>
                                    {{ $user->defaultAddress->label }}
                                </strong><br>
                                {{ $user->defaultAddress->recipient_name }}<br>
                                {{ $user->defaultAddress->phone }}<br>
                                {{ $user->defaultAddress->address_line }}<br>
                                {{ $user->defaultAddress->village }},
                                {{ $user->defaultAddress->district }},
                                {{ $user->defaultAddress->city }},
                                {{ $user->defaultAddress->province }}
                                {{ $user->defaultAddress->postal_code }}<br>
                                {{ $user->defaultAddress->country }}
                            </p>
                        @else
                            <p class="store-account-card-text">
                                You haven't added a default shipping address.
                            </p>
                        @endif

                        <a href="{{ route('account.addresses.index') }}"
                            class="store-account-card-link">
                            Manage Addresses
                            <i class="bi bi-arrow-right"></i>
                        </a>
                    </div>
                </div>

            </div>
        </div>
    </div>
</section>
@endsection
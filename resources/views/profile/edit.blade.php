@extends('layouts.storefront')

@section('title', 'Profile')

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

                <li class="breadcrumb-item">
                    <a href="{{ route('account.index') }}">
                        My Account
                    </a>
                </li>

                <li class="breadcrumb-item active" aria-current="page">
                    Profile
                </li>

            </ol>
        </nav>
    </div>
</div>


{{-- =========================================================
    HEADER
========================================================= --}}
<section class="store-section pb-3">
    <div class="container">

        <div class="store-section-header">

            <div>
                <span class="store-section-eyebrow">
                    Account
                </span>

                <h1 class="store-section-title">Profile</h1>
                <p class="store-section-subtitle">.Manage your personal information and account security</p>
            </div>

        </div>

        @if (session('status') === 'profile-updated')
            <div class="alert alert-success mt-3">
                Profile updated successfully
            </div>
        @endif

    </div>
</section>


{{-- =========================================================
    PROFILE CONTENT
========================================================= --}}
<section class="pb-5">
    <div class="container">

        <div class="row g-4">

            {{-- =================================================
                PROFILE INFORMATION
            ================================================= --}}
            <div class="col-lg-8">

                <div class="store-profile-panel">

                    <div class="store-profile-panel-header">
                        <div>
                            <h2>Personal Information</h2>
                            <p>Update your basic account information.</p>
                        </div>
                    </div>


                    <form
                        method="POST"
                        action="{{ route('profile.update') }}"
                        enctype="multipart/form-data"
                    >
                        @csrf
                        @method('PATCH')


                        {{-- =====================================
                            AVATAR
                        ====================================== --}}
                        <div class="store-profile-avatar-section">

                            <div class="store-profile-avatar">

                                @if ($user->avatar)
                                    <img
                                        src="{{ asset('storage/' . $user->avatar) }}"
                                        alt="{{ $user->name }}"
                                    >
                                @else
                                    <span>
                                        {{ strtoupper(substr($user->name, 0, 1)) }}
                                    </span>
                                @endif

                            </div>

                            <div class="store-profile-avatar-info">

                                <label
                                    for="avatar"
                                    class="form-label"
                                >
                                    Profile Photo
                                </label>

                                <input
                                    id="avatar"
                                    name="avatar"
                                    type="file"
                                    class="form-control"
                                    accept=".jpg,.jpeg,.png,.webp"
                                >

                                <div class="form-text">
                                    JPG, PNG, atau WEBP. Maksimal 2 MB.
                                </div>

                                @error('avatar')
                                    <div class="text-danger small mt-1">
                                        {{ $message }}
                                    </div>
                                @enderror

                            </div>

                        </div>


                        <div class="row g-3">

                            {{-- NAME --}}
                            <div class="col-md-6">

                                <label
                                    for="name"
                                    class="form-label"
                                >
                                    Full Name
                                </label>

                                <input
                                    id="name"
                                    name="name"
                                    type="text"
                                    class="form-control @error('name') is-invalid @enderror"
                                    value="{{ old('name', $user->name) }}"
                                    required
                                    autocomplete="name"
                                >

                                @error('name')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror

                            </div>


                            {{-- EMAIL --}}
                            <div class="col-md-6">

                                <label
                                    for="email"
                                    class="form-label"
                                >
                                    Email Address
                                </label>

                                <input
                                    id="email"
                                    name="email"
                                    type="email"
                                    class="form-control @error('email') is-invalid @enderror"
                                    value="{{ old('email', $user->email) }}"
                                    required
                                    autocomplete="email"
                                >

                                @error('email')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror

                            </div>


                            {{-- PHONE --}}
                            <div class="col-md-6">

                                <label
                                    for="phone"
                                    class="form-label"
                                >
                                    Phone Number
                                </label>

                                <input
                                    id="phone"
                                    name="phone"
                                    type="tel"
                                    class="form-control @error('phone') is-invalid @enderror"
                                    value="{{ old('phone', $user->phone) }}"
                                    autocomplete="tel"
                                    placeholder="08xxxxxxxxxx"
                                >

                                @error('phone')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror

                            </div>


                            {{-- BIRTH DATE --}}
                            <div class="col-md-6">

                                <label
                                    for="birth_date"
                                    class="form-label"
                                >
                                    Date of Birth
                                </label>

                                <input
                                    id="birth_date"
                                    name="birth_date"
                                    type="date"
                                    class="form-control @error('birth_date') is-invalid @enderror"
                                    value="{{ old(
                                        'birth_date',
                                        $user->birth_date?->format('Y-m-d')
                                    ) }}"
                                    autocomplete="bday"
                                >

                                @error('birth_date')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror

                            </div>


                            {{-- ADDRESS --}}
                            <div class="col-12">

                                <label
                                    for="address"
                                    class="form-label"
                                >
                                    Address
                                </label>

                                <textarea
                                    id="address"
                                    name="address"
                                    class="form-control @error('address') is-invalid @enderror"
                                    rows="4"
                                    autocomplete="street-address"
                                    placeholder="Alamat lengkap"
                                >{{ old('address', $user->address) }}</textarea>

                                @error('address')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror

                            </div>


                            {{-- CITY --}}
                            <div class="col-md-4">

                                <label
                                    for="city"
                                    class="form-label"
                                >
                                    City
                                </label>

                                <input
                                    id="city"
                                    name="city"
                                    type="text"
                                    class="form-control @error('city') is-invalid @enderror"
                                    value="{{ old('city', $user->city) }}"
                                    autocomplete="address-level2"
                                >

                                @error('city')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror

                            </div>


                            {{-- PROVINCE --}}
                            <div class="col-md-4">

                                <label
                                    for="province"
                                    class="form-label"
                                >
                                    Province
                                </label>

                                <input
                                    id="province"
                                    name="province"
                                    type="text"
                                    class="form-control @error('province') is-invalid @enderror"
                                    value="{{ old('province', $user->province) }}"
                                    autocomplete="address-level1"
                                >

                                @error('province')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror

                            </div>


                            {{-- POSTAL CODE --}}
                            <div class="col-md-4">

                                <label
                                    for="postal_code"
                                    class="form-label"
                                >
                                    Postal Code
                                </label>

                                <input
                                    id="postal_code"
                                    name="postal_code"
                                    type="text"
                                    class="form-control @error('postal_code') is-invalid @enderror"
                                    value="{{ old('postal_code', $user->postal_code) }}"
                                    autocomplete="postal-code"
                                >

                                @error('postal_code')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror

                            </div>

                        </div>


                        <div class="store-profile-form-actions">

                            <a
                                href="{{ route('account.index') }}"
                                class="btn btn-outline-secondary"
                            >
                                Cancel
                            </a>

                            <button
                                type="submit"
                                class="btn btn-primary"
                            >
                                <i class="bi bi-check2 me-1"></i>
                                Save Changes
                            </button>

                        </div>

                    </form>

                </div>

            </div>


            {{-- =================================================
                ACCOUNT SIDEBAR
            ================================================= --}}
            <div class="col-lg-4">

                <div class="store-profile-sidebar">

                    <div class="store-profile-sidebar-item">

                        <div class="store-profile-sidebar-icon">
                            <i class="bi bi-person-circle"></i>
                        </div>

                        <div>
                            <strong>
                                {{ $user->name }}
                            </strong>

                            <span>
                                {{ $user->email }}
                            </span>
                        </div>

                    </div>


                    <div class="store-profile-sidebar-divider"></div>


                    <a
                        href="{{ route('account.index') }}"
                        class="store-profile-sidebar-link"
                    >
                        <i class="bi bi-grid"></i>
                        <span>My Account</span>
                        <i class="bi bi-chevron-right"></i>
                    </a>


                    <a
                        href="{{ route('orders.index') }}"
                        class="store-profile-sidebar-link"
                    >
                        <i class="bi bi-bag"></i>
                        <span>My Orders</span>
                        <i class="bi bi-chevron-right"></i>
                    </a>


                    <a
                        href="{{ route('profile.edit') }}"
                        class="store-profile-sidebar-link active"
                    >
                        <i class="bi bi-person"></i>
                        <span>Profile</span>
                        <i class="bi bi-chevron-right"></i>
                    </a>

                </div>

            </div>

        </div>


        {{-- =====================================================
            SECURITY
        ====================================================== --}}
        <div class="row g-4 mt-1">
            <div class="col-lg-8">
                <div class="store-profile-panel">
                    <div class="store-profile-panel-header">
                        <div>
                            <h2>Password</h2>
                            <p>Use a strong password to keep your account safe from</p>
                        </div>
                    </div>
                    @include('profile.partials.update-password-form')
                </div>
            </div>
        </div>


        {{-- =====================================================
            DANGER ZONE
        ====================================================== --}}
        <div class="row g-4 mt-1">
            <div class="col-lg-8">
                <div class="store-profile-danger">
                    @include('profile.partials.delete-user-form')
                </div>
            </div>
        </div>

    </div>
</section>

@endsection

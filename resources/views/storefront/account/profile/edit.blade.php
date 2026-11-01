@extends('layouts.storefront')

@section('title', config('app.name') . ' - Profile Settings')

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
                <li class="breadcrumb-item">
                    Account
                </li>
                <li class="breadcrumb-item active" aria-current="page">
                    Profile Settings
                </li>
            </ol>
        </nav>
    </div>
</div>

@include('components.account.page-header', [
    'title' => 'Profile Settings',
    'description' => 'Manage your personal information and account security.',
    'eyebrow' => 'My Account',
])

<section class="pb-5">
    <div class="container">
        <div class="row g-4">
            <aside class="col-lg-4">
                @include('components.account.sidebar', ['user' => $user])
            </aside>
            <div class="col-lg-8">
                @if (session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                <div class="store-profile-panel">
                    <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data">
                        @csrf
                        @method('PATCH')

                        {{-- =====================================
                            AVATAR
                        ====================================== --}}
                        <div class="store-profile-avatar-section">
                            <div class="store-profile-avatar">
                                @if ($user->avatar)
                                    <img src="{{ asset('storage/' . $user->avatar) }}" alt="{{ $user->name }}">
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
                                <label for="name" class="form-label">
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

                                <label for="email" class="form-label">
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

                                <label for="birth_date" class="form-label">
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
                                class="store-btn-primary"
                            >
                                <i class="bi bi-check2 me-1"></i>
                                Save Changes
                            </button>

                        </div>

                    </form>
                </div>
                
                <div class="store-profile-panel p-3 mt-4">
                    <h2 class="h5 mb-3">Change Password</h2>
                    <p class="text-muted mb-4">
                        Update your account password to keep your account secure.
                    </p>
                
                    <form method="POST" action="{{ route('profile.password.update') }}">
                        @csrf
                        @method('PUT')
                
                        {{-- CURRENT PASSWORD --}}
                        <div class="mb-3">
                            <label for="current_password" class="form-label">
                                Current Password
                            </label>
                            <input
                                id="current_password"
                                name="current_password"
                                type="password"
                                class="form-control @error('current_password', 'passwordUpdate') is-invalid @enderror"
                                autocomplete="current-password"
                                required
                            >
                            @error('current_password', 'passwordUpdate')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                
                        {{-- NEW PASSWORD --}}
                        <div class="mb-3">
                            <label for="password" class="form-label">
                                New Password
                            </label>
                            <input
                                id="password"
                                name="password"
                                type="password"
                                class="form-control @error('password', 'passwordUpdate') is-invalid @enderror"
                                autocomplete="new-password"
                                required
                            >
                            @error('password', 'passwordUpdate')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                
                        {{-- CONFIRM PASSWORD --}}
                        <div class="mb-3">
                            <label for="password_confirmation" class="form-label">
                                Confirm New Password
                            </label>
                            <input
                                id="password_confirmation"
                                name="password_confirmation"
                                type="password"
                                class="form-control"
                                autocomplete="new-password"
                                required
                            >
                        </div>
                
                        <div class="store-profile-form-actions">
                            <button type="submit" class="store-btn-primary">
                                <i class="bi bi-shield-lock me-1"></i>
                                Update Password
                            </button>
                        </div>
                    </form>
                </div>
                
            </div>
        </div>
    </div>
</section>
@endsection
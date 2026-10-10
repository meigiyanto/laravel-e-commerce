
@extends('layouts.auth')

@section('title', 'Admin Login')

@section('content')
    <div class="auth-header">
        <span class="auth-eyebrow text-center">
            ADMINISTRATION PANEL
        </span>

        <h1 class="text-center">Admin Login</h1>

        <p class="text-center">
            Sign in to manage your online store.
        </p>
    </div>

    @if (session('success'))
        <div class="alert alert-success" role="status">
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger" role="alert">
            {{ session('error') }}
        </div>
    @endif

    <form
        method="POST"
        action="{{ route('admin.login.authenticate') }}"
        class="auth-form"
    >
        @csrf

        <div class="form-group">
            <label for="email" class="form-label">
                Admin Email
            </label>

            <input
                id="email"
                type="email"
                name="email"
                value="{{ old('email') }}"
                required
                autofocus
                autocomplete="username"
                class="form-input"
                placeholder="admin@example.com"
                aria-describedby="email-error"
            >

            @error('email')
                <p id="email-error" class="form-error">
                    {{ $message }}
                </p>
            @enderror
        </div>

        <div class="form-group">
            <div class="form-label-row">
                <label for="password" class="form-label">
                    Password
                </label>

                @if (Route::has('password.request'))
                    <a
                        href="{{ route('password.request') }}"
                        class="auth-link"
                    >
                        Forgot password?
                    </a>
                @endif
            </div>

            <input
                id="password"
                type="password"
                name="password"
                required
                autocomplete="current-password"
                class="form-input"
                placeholder="Enter your password"
                aria-describedby="password-error"
            >

            @error('password')
                <p id="password-error" class="form-error">
                    {{ $message }}
                </p>
            @enderror
        </div>

        <label class="checkbox-label">
            <input
                type="checkbox"
                name="remember"
                value="1"
                @checked(old('remember'))
            >

            <span>Remember me</span>
        </label>

        <button
            type="submit"
            class="btn btn-block btn-primary btn-full"
        >
            Sign In to Admin
        </button>
    </form>

    <div class="auth-footer">
        <a href="{{ route('storefront.home') }}" class="auth-link">
            &larr; Back to Store
        </a>
    </div>
@endsection

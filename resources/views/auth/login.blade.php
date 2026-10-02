@extends('layouts.guest')

@section('title', 'Admin Dashboard')
@section('header', 'Admin Dashboard')

@section('content')
    <div class="auth-header">
        <h1>Welcome Back</h1>

        <p>
            Login untuk melanjutkan ke akun Anda.
        </p>
    </div>

    <x-auth-session-status
        class="alert alert-success"
        :status="session('status')"
    />

    <form method="POST" action="{{ route('login') }}" class="auth-form">
        @csrf

        <div class="form-group">
            <label for="email" class="form-label">Email</label>

            <input
                id="email"
                type="email"
                name="email"
                value="{{ old('email') }}"
                required
                autofocus
                autocomplete="username"
                class="form-input"
            >

            @error('email')
                <p class="form-error">{{ $message }}</p>
            @enderror
        </div>

        <div class="form-group">
            <label for="password" class="form-label">
                Password
            </label>

            <input
                id="password"
                type="password"
                name="password"
                required
                autocomplete="current-password"
                class="form-input"
            >

            @error('password')
                <p class="form-error">{{ $message }}</p>
            @enderror
        </div>

        <div class="form-row">
            <label class="checkbox-label">
                <input
                    type="checkbox"
                    name="remember"
                >

                <span>Remember me</span>
            </label>

            @if (Route::has('password.request'))
                <a
                    href="{{ route('password.request') }}"
                    class="auth-link"
                >
                    Lupa password?
                </a>
            @endif
        </div>

        <button type="submit" class="btn btn-primary btn-full">
            Login
        </button>

    </form>

    <div class="auth-footer">
        <span>Belum punya akun?</span>

        <a href="{{ route('register') }}" class="auth-link">
            Daftar sekarang
        </a>
    </div>

@endsection
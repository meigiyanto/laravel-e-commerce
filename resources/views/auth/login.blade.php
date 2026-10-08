@extends('layouts.auth')

@section('title', 'Login')

@section('content')
    <div class="auth-header">
        <span class="auth-eyebrow text-center">E-Commerce Store</span>

        <h1 class="text-center">Welcome back</h1>
        <p class="text-center">Login to continue to your account </p>
    </div>

    @if(session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif


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
                autocomplete="username"
                class="form-input"
                placeholder="name@email.com"
            >

            @error('email')
                <p class="form-error">
                    {{ $message }}
                </p>
            @enderror
        </div>
        <div class="form-group">
            <div class="form-label-row">
                <label
                    for="password"
                    class="form-label"
                >
                    Password
                </label>
                @if (Route::has('password.request'))
                    <a
                        href="{{ route('password.request') }}"
                        class="auth-link"
                    >
                        forgot password?
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
                placeholder="Input password"
            >

            @error('password')
                <p class="form-error">
                    {{ $message }}
                </p>
            @enderror

        </div>


        <label class="checkbox-label">
            <input
                type="checkbox"
                name="remember"
            >
            <span>Remember me</span>
        </label>


        <button type="submit" class="btn btn-block btn-primary btn-full">Login</button>
    </form>

    <div class="auth-footer">
        <span>Have not an account?</span>
        <a
            href="{{ route('register') }}"
            class="auth-link"
        >
            Register now
        </a>
    </div>
@endsection

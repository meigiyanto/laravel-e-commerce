@extends('layouts.auth')

@section('title', 'Register')

@section('content')
    <div class="auth-header">
        <span class="auth-eyebrow text-center">E-Commerce Store</span>
        <h1 class="text-center">Create your account</h1>
        <p class="text-center">Create a new account to start shopping </p>
    </div>

    <form
        method="POST"
        action="{{ route('register') }}"
        class="auth-form"
    >
        @csrf

        <div class="form-group">
            <label
                for="name"
                class="form-label"
            >
                Name
            </label>

            <input
                id="name"
                type="text"
                name="name"
                value="{{ old('name') }}"
                required
                autocomplete="name"
                class="form-input"
                placeholder="Full Name"
            >
            @error('name')
                <p class="form-error">
                    {{ $message }}
                </p>
            @enderror
        </div>

        <div class="form-group">
            <label
                for="email"
                class="form-label"
            >
                Email
            </label>

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
            <label
                for="password"
                class="form-label"
            >
                Password
            </label>

            <input
                id="password"
                type="password"
                name="password"
                required
                autocomplete="new-password"
                class="form-input"
                placeholder="At least 8 character"
            >

            @error('password')
                <p class="form-error">
                    {{ $message }}
                </p>
            @enderror

        </div>


        <div class="form-group">
            <label
                for="password_confirmation"
                class="form-label"
            >
                Password Confirmation
            </label>

            <input
                id="password_confirmation"
                type="password"
                name="password_confirmation"
                required
                class="form-input"
                placeholder="Confirmation password"
            >

            @error('password_confirmation')
                <p class="form-error">
                    {{ $message }}
                </p>
            @enderror
        </div>


        <button
            type="submit"
            class="btn btn-block btn-primary btn-full"
        >
            Create account
        </button>

    </form>


    <div class="auth-footer">
        <span>Already have an account?</span>
        <a
            href="{{ route('login') }}"
            class="auth-link"
        >
            Login
        </a>
    </div>
@endsection

@extends('layouts.guest')

@section('title', 'Register')

@section('content')
    <div class="auth-header">
        <span class="auth-eyebrow text-center">E-Commerce Store</span>
        <h1 class="text-center">Create your account</h1>
        <p class="text-center">Buat akun baru untuk mulai berbelanja.</p>
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
                Nama
            </label>

            <input
                id="name"
                type="text"
                name="name"
                value="{{ old('name') }}"
                required
                autocomplete="name"
                class="form-input"
                placeholder="Nama lengkap"
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
                placeholder="nama@email.com"
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
                placeholder="Minimal 8 karakter"
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
                Konfirmasi Password
            </label>

            <input
                id="password_confirmation"
                type="password"
                name="password_confirmation"
                required
                autocomplete="new-password"
                class="form-input"
                placeholder="Ulangi password"
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
            Buat akun
        </button>

    </form>


    <div class="auth-footer">
        <span>Sudah punya akun?</span>
        <a
            href="{{ route('login') }}"
            class="auth-link"
        >
            Login
        </a>
    </div>
@endsection

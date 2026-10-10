@extends('layouts.auth')

@section('title', 'Confirm Password')

@section('content')
    <div class="auth-header">
        <h1 class="text-center">Confirm Password</h1>

        <p class="text-center">For security, please confirm the password before continuing </p>
    </div>

    <form method="POST" action="{{ route('password.confirm') }}" class="auth-form">
        @csrf

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

        <button type="submit" class="btn btn-primary btn-full">
            Confirm
        </button>
    </form>

@endsection

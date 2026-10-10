@extends('layouts.auth')

@section('title', 'Email Verification')

@section('content')
    <div class="auth-header">
        <span class="auth-eyebrow text-center">E-Commerce Store</span>
        <h1 class="text-center">Email Verification</h1>

        <p class="text-center">Thank you for registering. Before continuing, please verify your email address.</p>
    </div>

    @if (session('status') === 'verification-link-sent')
        <div class="alert alert-success">
            A new verification link has been sent to your email address.
        </div>
    @endif

    <div class="auth-form">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf

            <button type="submit" class="btn btn-primary btn-full mb-3">
                Resend email verification
            </button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf

            <button type="submit" class="btn btn-secondary btn-full">
                Logout
            </button>
        </form>

    </div>

@endsection

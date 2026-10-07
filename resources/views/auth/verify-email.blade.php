@extends('layouts.guest')

@section('title', 'Email Verification')

@section('content')
    <div class="auth-header">
        <span class="auth-eyebrow text-center">E-Commerce Store</span>
        <h1 class="text-center">Verifikasi Email</h1>

        <p class="text-center">
            Terima kasih sudah mendaftar.
            Sebelum melanjutkan, silakan verifikasi alamat email Anda
            melalui link yang telah kami kirimkan.
        </p>
    </div>

    @if (session('status') === 'verification-link-sent')
        <div class="alert alert-success">
            Link verifikasi baru telah dikirim ke alamat email Anda.
        </div>
    @endif

    <div class="auth-actions">

        <form method="POST" action="{{ route('verification.send') }}">
            @csrf

            <button type="submit" class="btn btn-primary btn-full">
                Kirim Ulang Email Verifikasi
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

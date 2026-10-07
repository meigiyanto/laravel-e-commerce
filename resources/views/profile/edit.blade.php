@extends('layouts.storefront')

@section('title', 'Edit Profile')
@section('header', 'Edit Profile')

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
                    Profile
                </li>
                <li class="breadcrumb-item active" aria-current="page">
                    Edit Profile
                </li>
            </ol>
        </nav>
    </div>
</div>

<section class="store-section pb-3">
    <div class="container">
        <div class="store-section-header">
            <div>
                <h1 class="store-section-title">Profile</h1>
                <p class="store-section-subtitle">Manage your information.</p>
            </div>
        </div>
        @if(session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-error">
                {{ session('error') }}
            </div>
        @endif
    </div>
</section>

<section>
    <div class="container">
        <div class="card mb-3">
            <div class="card-body">
                @include('profile.partials.update-profile-information-form')
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-body">
                @include('profile.partials.update-password-form')
            </div>
        </div>
        <div class="card mb-3">
            <div class="card-body">
                @include('profile.partials.delete-user-form')
            </div>
        </div>
    </div>
</section>
@endsection

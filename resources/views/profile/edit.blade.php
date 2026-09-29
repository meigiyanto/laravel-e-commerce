@extends('layouts.app')

@section('title', 'Edit Profile')

@section('header', 'Edit Profile')

@section('content')
<div class="page-container">

    <div class="page-title">
        <h1>Profile</h1>
        <p>Kelola informasi akun Anda.</p>
    </div>

    <div class="grid">

        <div class="content-card">
            @include('profile.partials.update-profile-information-form')
        </div>

        <div class="content-card">
            @include('profile.partials.update-password-form')
        </div>

        <div class="content-card">
            @include('profile.partials.delete-user-form')
        </div>

    </div>

</div>
@endsection

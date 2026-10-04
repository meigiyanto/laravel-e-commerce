@extends('layouts.customer')

@section('title', 'Edit Profile')
@section('header', 'Edit Profile')

@section('content')
     <div class="nk-block-head nk-block-head-sm">
        <div class="nk-block-between">
            <div class="nk-block-head-content">
                <h3 class="nk-block-title page-title">Profile</h3>
                <p>Manage your information</p>
                <div class="nk-block-des text-soft">
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
            </div>

        </div>
    </div>

    <div class="card card-bordered">
        <div class="card-inner">
            @include('profile.partials.update-profile-information-form')
        </div>
    </div>

    <div class="card card-bordered">
        <div class="card-inner">
            @include('profile.partials.update-password-form')
        </div>
    </div>
    <div class="card card-bordered">
        <div class="card-inner">
            @include('profile.partials.delete-user-form')
        </div>
    </div>
@endsection

@extends('layouts.app')

@section('title', 'Dashboard')

@section('header', 'Dashboard')

@section('content')

<div class="nk-content">
    <div class="container-fluid">
        <div class="nk-content-inner">
            <div class="nk-content-body">

                <div class="nk-block-head nk-block-head-sm">
                    <div class="nk-block-head-content">

                        <h3 class="nk-block-title page-title">
                            Dashboard
                        </h3>

                        <div class="nk-block-des text-soft">
                            <p>
                                Selamat datang kembali,
                                {{ Auth::user()->name }}.
                            </p>
                        </div>

                    </div>
                </div>

                <div class="nk-block">

                    <div class="card card-bordered">
                        <div class="card-inner">

                            <h5 class="title">
                                Selamat Datang 👋
                            </h5>

                            <p>
                                Anda berhasil login sebagai
                                <strong>user</strong>.
                            </p>

                            <p class="text-soft">
                                Email:
                                {{ Auth::user()->email }}
                            </p>

                            <a
                                href="{{ route('profile.edit') }}"
                                class="btn btn-primary"
                            >
                                Edit Profile
                            </a>

                        </div>
                    </div>

                </div>

            </div>
        </div>
    </div>
</div>

@endsection

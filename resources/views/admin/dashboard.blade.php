@extends('layouts.app')

@section('title', 'Admin Dashboard')
@section('header', 'Admin Dashboard')

@section('content')
<div class="nk-content-body">

    <div class="nk-block-head nk-block-head-sm">
        <div class="nk-block-between">
            <div class="nk-block-head-content">
                <h3 class="nk-block-title page-title">
                    Admin Dashboard
                </h3>

                <div class="nk-block-des text-soft">
                    <p>
                        Selamat datang kembali,
                        {{ Auth::user()->name }}.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div class="nk-block">
        <div class="row g-gs">
            <div class="col-md-6 col-lg-4">
                <div class="card card-bordered">
                    <div class="card-inner">
                        <div class="card-title-group align-start mb-0">
                            <div class="card-title">
                                <h6 class="title">
                                    User Management
                                </h6>
                            </div>

                            <div class="card-tools">
                                <em class="icon ni ni-users"></em>
                            </div>
                        </div>

                        <div class="card-amount mt-2">
                            <span class="amount">
                                Manage Users
                            </span>
                        </div>

                        <div class="mt-3">
                            <a
                                href="{{ route('admin.users.index') }}"
                                class="btn btn-primary"
                            >
                                Kelola User
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-lg-4">
                <div class="card card-bordered">
                    <div class="card-inner">
                        <div class="card-title-group align-start mb-0">
                            <div class="card-title">
                                <h6 class="title">
                                    Categories
                                </h6>
                            </div>

                            <div class="card-tools">
                                <em class="icon ni ni-list-thumb"></em>
                            </div>
                        </div>

                        <div class="card-amount mt-2">
                            <span class="amount">
                                Manage Categories
                            </span>
                        </div>

                        <div class="mt-3">
                            <a
                                href="{{ route('admin.categories.index') }}"
                                class="btn btn-primary"
                            >
                                Kelola Category
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-lg-4">
                <div class="card card-bordered">
                    <div class="card-inner">
                        <div class="card-title-group align-start mb-0">
                            <div class="card-title">
                                <h6 class="title">
                                    Products
                                </h6>
                            </div>

                            <div class="card-tools">
                                <em class="icon ni ni-package"></em>
                            </div>
                        </div>

                        <div class="card-amount mt-2">
                            <span class="amount">
                                Manage Products
                            </span>
                        </div>

                        <div class="mt-3">
                            <a
                                href="{{ route('admin.products.index') }}"
                                class="btn btn-primary"
                            >
                                Kelola Product
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

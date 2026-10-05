@extends('layouts.storefront')

@section('title', 'Dashboard')

@section('content')
    <div class="nk-block-head nk-block-head-sm">
        <div class="nk-block-between">
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
    </div>

    <div class="nk-block">
        <div class="row g-gs">
            <div class="col-md-6 col-xl-3">
                <div class="card card-bordered">
                    <div class="card-inner">
                        <div class="card-title-group">
                            <div class="card-title">
                                <h6 class="title">
                                    Account
                                </h6>
                            </div>

                            <div class="card-tools">
                                <div class="icon-circle icon-circle-lg bg-primary-dim">
                                    <em class="icon ni ni-user"></em>
                                </div>
                            </div>
                        </div>
                        <div class="data">
                            <div class="data-group">
                                <div class="amount">
                                    {{ Auth::user()->name }}
                                </div>
                            </div>
                        </div>
                        <div class="data">
                            <span class="sub-text">
                                {{ Auth::user()->email }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-xl-3">
                <div class="card card-bordered">
                    <div class="card-inner">
                        <div class="card-title-group">
                            <div class="card-title">
                                <h6 class="title">
                                    My Orders
                                </h6>
                            </div>
                            <div class="card-tools">
                                <div class="icon-circle icon-circle-lg bg-info-dim">
                                    <em class="icon ni ni-bag"></em>
                                </div>
                            </div>
                        </div>
                        <div class="data">
                            <div class="amount">
                                My Orders
                            </div>
                        </div>
                        <div class="data">
                            <a
                                href="{{ route('orders.index') }}"
                                class="btn btn-sm btn-outline-primary mt-2"
                            >
                                View Orders
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-xl-3">
                <div class="card card-bordered">
                    <div class="card-inner">
                        <div class="card-title-group">
                            <div class="card-title">
                                <h6 class="title">
                                    Profile
                                </h6>
                            </div>
                            <div class="card-tools">
                                <div class="icon-circle icon-circle-lg bg-success-dim">
                                    <em class="icon ni ni-account-setting"></em>
                                </div>
                            </div>
                        </div>
                        <div class="data">
                            <div class="amount">
                                Account
                            </div>
                        </div>
                        <div class="data">
                            <a
                                href="{{ route('profile.edit') }}"
                                class="btn btn-sm btn-outline-primary mt-2"
                            >
                                Edit Profile
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-xl-3">
                <div class="card card-bordered">
                    <div class="card-inner">
                        <div class="card-title-group">
                            <div class="card-title">
                                <h6 class="title">
                                    Store
                                </h6>
                            </div>
                            <div class="card-tools">
                                <div class="icon-circle icon-circle-lg bg-warning-dim">
                                    <em class="icon ni ni-shop"></em>
                                </div>
                            </div>
                        </div>
                        <div class="data">
                            <div class="amount">
                                MeiStore
                            </div>
                        </div>
                        <div class="data">
                            <a
                                href="{{ route('storefront.home') }}"
                                class="btn btn-sm btn-outline-primary mt-2"
                            >
                                Visit Store
                            </a>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
@endsection

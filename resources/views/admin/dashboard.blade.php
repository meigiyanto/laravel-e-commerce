@extends('layouts.admin')

@section('title', 'Admin Dashboard')

@section('content')
    <div class="nk-block-head nk-block-head-sm">
        <div class="nk-block-between">
            <div class="nk-block-head-content">
                <h3 class="nk-block-title page-title">
                    Dashboard
                </h3>

                <div class="nk-block-des text-soft">
                    <p>Hello <strong>{{ Auth::user()->name }}</strong>, Welcome to your dashboard.</p>
                </div>
            </div>
        </div>
    </div>

    <div class="nk-block">
        <div class="row g-gs">
            <div class="col-sm-6 col-md-3 col-lg-3">
                <div class="card card-bordered">
                    <div class="card-inner">
                        <div class="card-title-group align-start mb-0">
                            <div class="card-title">
                                <h6 class="title">
                                    Products
                                </h6>
                            </div>
                            <div class="card-tools">
                                <div class="icon-circle icon-circle-lg bg-primary-dim">
                                    <em class="icon ni ni-package"></em>
                                </div>
                            </div>
                        </div>
                        <div class="data">
                            <div class="amount">
                                <strong>{{ $productsCount ?? 0 }}</strong>
                            </div>
                            <span class="sub-text">
                                Total Products
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-sm-6 col-md-3 col-lg-3">
                <div class="card card-bordered">
                    <div class="card-inner">
                        <div class="card-title-group align-start mb-0">
                            <div class="card-title">
                                <h6 class="title">
                                    Orders
                                </h6>
                            </div>

                            <div class="card-tools">
                                <div class="icon-circle icon-circle-lg bg-success-dim">
                                    <em class="icon ni ni-cart"></em>
                               </div>
                            </div>
                        </div>

                        <div class="data">
                            <div class="amount">
                                <strong>{{ $ordersCount ?? 0 }}</strong>
                            </div>

                            <span class="sub-text">
                                Total Orders
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-sm-6 col-md-3 col-lg-3">
                <div class="card card-bordered">
                    <div class="card-inner">
                        <div class="card-title-group align-start mb-0">
                            <div class="card-title">
                                <h6 class="title">
                                    Customers
                                </h6>
                            </div>
                            <div class="card-tools">
                                <div class="icon-circle icon-circle-lg bg-info-dim">                                    <em class="icon ni ni-users"></em>
                                </div>
                            </div>
                        </div>
                        <div class="data">
                            <div class="amount">
                                <strong>{{ $usersCount ?? 0 }}</strong>
                            </div>
                            <span class="sub-text">
                                Total Customers
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-sm-6 col-md-3 col-lg-3">
                <div class="card card-bordered">
                    <div class="card-inner">
                        <div class="card-title-group align-start mb-0">
                            <div class="card-title">
                                <h6 class="title">
                                    Categories
                                </h6>
                            </div>
                            <div class="card-tools">
                                <div class="icon-circle icon-circle-lg bg-warning-dim">
                                    <em class="icon ni ni-list"></em>

                                </div>
                            </div>
                        </div>
                        <div class="data">
                            <div class="amount">
                                <strong>{{ $categoriesCount ?? 0 }}</strong>
                            </div>
                            <span class="sub-text">
                                Total Categories
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

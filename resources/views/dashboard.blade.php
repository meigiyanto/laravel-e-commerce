@extends('layouts.storefront')

@section('title', 'Dashboard')

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
                    <li class="breadcrumb-item active" aria-current="page">
                        Account
                    </li>
                </ol>
            </nav>
        </div>
    </div>

    <section class="store-section pb-3">
        <div class="container">
            <div class="store-section-header">
                <div>
                    <h1 class="store-section-title">Dashboard<h1>
                    <p class="store-section-subtitle">Selamat datang kembali, {{ Auth::user()->name }}.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="pb-4">
        <div class="container">
            <div class="row g-gs">
                <div class="col-md-6 col-xl-3 mb-3">
                    <div class="card">
                        <div class="card-body">
                            <div class="card-title">                                
                                <h6 class="title fw-bold"><em class="bi bi-person"></em> Account</h6>
                                <p>{{ Auth::user()->name }}</p>
                                <p>{{ Auth::user()->email }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 col-xl-3 mb-3">
                    <div class="card">
                        <div class="card-body">
                            <div class="card-title">                                
                                <h6 class="title fw-bold"><em class="icon bi bi-bag"></em> My Orders</h6>
                                <p>My Orders</p>
                                <a href="{{ route('orders.index') }}" class="btn btn-sm btn-outline-primary mt-2">View Orders</a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 col-xl-3 mb-3">
                    <div class="card">
                        <div class="card-body">
                            <div class="card-title">
                                <h6 class="title fw-bold"><em class="bi bi-person-circle"></em> Profile</h6>
                                <p>Account</p>
                                <a href="{{ route('profile.edit') }}" class="btn btn-sm btn-outline-primary mt-2">Edit Profile</a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 col-xl-3">
                    <div class="card">
                        <div class="card-body">
                            <div class="card-title">                                
                                <h6 class="title fw-bold"><em class="icon bi bi-shop"></em> Store</h6>    
                                <p>MeiStore</p>
                                <a href="{{ route('storefront.home') }}" class="btn btn-sm btn-outline-primary mt-2">Visit Store</a>                          
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
@endsection

@extends('layouts.app')

@section('title', 'Dashboard')

@section('header', 'Dashboard')

@section('content')
<div class="page-container">

    <div class="page-title">
        <h1>Dashboard</h1>
        <p>Selamat datang kembali, {{ Auth::user()->name }}</p>
    </div>

    <div class="grid grid-3">

        <div class="stat-card">
            <h3>Total Users</h3>
            <div class="value">{{ \App\Models\User::count() }}</div>
        </div>

        <div class="stat-card">
            <h3>Total Categories</h3>
            <div class="value">{{ \App\Models\Category::count() }}</div>
        </div>

        <div class="stat-card">
            <h3>Total Products</h3>
            <div class="value">{{ \App\Models\Product::count() }}</div>
        </div>

        <div class="stat-card">
            <h3>Total Orders</h3>
            <div class="value">{{ \App\Models\Order::count() }}</div>
        </div>

        <!--
        <div class="stat-card">
            <p class="stat-label">
                Pelanggan
            </p>

            <p class="stat-value">
                0
            </p>
        </div>

        <div class="stat-card">
            <p class="stat-label">
                Pendapatan
            </p>

            <p class="stat-value">
                Rp 0
            </p>
        </div>
        -->

    </div>

</div>
@endsection

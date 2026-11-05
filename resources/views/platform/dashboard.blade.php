@extends('layouts.platform')

@section('title', 'Platform Dashboard')

@section('content')
<div class="nk-block-head nk-block-head-sm">
    <div class="nk-block-head-content">
        <h3 class="nk-block-title page-title">Platform Dashboard</h3>
        <p class="text-soft">Global platform overview.</p>
    </div>
</div>
<div class="row g-gs">
    @foreach ([
        'Total Tenants' => $tenantsCount,
        'Active Tenants' => $activeTenantsCount,
        'Total Stores' => $storesCount,
        'Total Users' => $usersCount,
    ] as $label => $value)
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card card-bordered">
                <div class="card-inner">
                    <h6 class="title">{{ $label }}</h6>
                    <div class="amount">{{ number_format($value) }}</div>
                </div>
            </div>
        </div>
    @endforeach
</div>
<a href="{{ route('platform.tenants.index') }}" class="btn btn-primary mt-3">Manage Tenants</a>
@endsection
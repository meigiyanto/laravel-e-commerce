@extends('layouts.app')

@section('title', 'Order Management')
@section('header', 'Order Management')

@section('content')
<div class="nk-content-body">
    {{-- Header --}}
    <div class="nk-block-head nk-block-head-sm">
        <div class="nk-block-between">
            <div class="nk-block-head-content">
                <h3 class="nk-block-title page-title">Order Management</h3>
                <div class="nk-block-des text-soft">
                    <p>Manage order custimer and order status</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Flash message --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Status Summary --}}
    <div class="row g-gs mb-4">
        @foreach($statuses as $itemStatus)
            @php
                $count = $statusCounts[$itemStatus] ?? 0;
                $statusClass = match ($itemStatus) {
                    'pending' => 'text-warning',
                    'processing' => 'text-info',
                    'shipped' => 'text-primary',
                    'completed' => 'text-success',
                    'cancelled' => 'text-danger',
                    default => 'text-soft',
                };
            @endphp

            <div class="col-6 col-md-4 col-xl">
                <a href="{{ route('admin.orders.index', ['status' => $itemStatus ]) }}" class="card card-bordered h-100">
                    <div class="card-inner">
                        <span class="text-soft">{{ ucfirst($itemStatus) }}</span>
                        <div class="fs-2 fw-bold mt-1 {{ $statusClass }}">{{ $count }}</div>
                    </div>
                </a>
            </div>
        @endforeach
    </div>

    {{-- Orders --}}
    <div class="card card-bordered">
        <div class="card-inner">
            {{-- Search & Filter --}}
            <form method="GET" action="{{ route('admin.orders.index') }}" class="row g-2 mb-4">
                <div class="col-md-6">
                    <input type="text" name="search" value="{{ $search }}" class="form-control" placeholder="Cari nomor order, customer, email, atau telepon">
                </div>
                <div class="col-md-3">
                    <select name="status" class="form-select">
                        <option value="">All Status</option>
                        @foreach($statuses as $itemStatus)
                            <option value="{{ $itemStatus }}" @selected($status === $itemStatus)>{{ ucfirst($itemStatus) }}</option>
                        @endforeach
                    </select>
                </div>


                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-lg btn-primary flex-grow-1">Filter</button>
                    @if($search || $status)
                        <a href="{{ route('admin.orders.index') }}" class="btn btn-secondary">
                            Reset
                        </a>
                    @endif
                </div>
            </form>

            {{-- Table --}}
            <div class="table-responsive">
                <table class="table table-middle js-datatable">
                    <thead>
                        <tr>
                            <th>Order</th>
                            <th>Customer</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th class="text-end no-sort">
                                Action
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($orders as $order)
                            @php
                                $statusClass = match ($order->status) {
                                    'pending' => 'bg-warning text-dark',
                                    'processing' => 'bg-info text-dark',
                                    'shipped' => 'bg-primary',
                                    'completed' => 'bg-success',
                                    'cancelled' => 'bg-danger',
                                    default => 'bg-secondary',
                                };
                            @endphp
                            <tr>
                                {{-- Order --}}
                                <td>
                                    <strong> #{{ $order->order_number }}</strong>
                                </td>
                                {{-- Customer --}}
                                <td>
                                    <div class="fw-bold">
                                        {{ $order->customer_name }}
                                    </div>
                                    <small class="text-soft">{{ $order->user?->email ?? '-' }}</small>
                                </td>
                                {{-- Total --}}
                                <td> Rp {{ number_format($order->total, 0, ',', '.') }}</td>
                                {{-- Status --}}
                                <td>
                                    <span class="badge {{ $statusClass }}">{{ ucfirst($order->status) }}</span>
                                </td>
                                {{-- Date --}}
                                <td>{{ optional($order->created_at)->format('d M Y H:i') ?? '-' }} </td>
                                {{-- Action --}}
                                <td class="text-end">
                                    <a href="{{ route('admin.orders.show', $order) }}" class="btn btn-sm btn-outline-primary">
                                        Detail
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                @if($orders->isEmpty())
                    <div class="text-center text-soft py-4">
                        Order not found.
                    </div>
                @endif
            </div>

        </div>
    </div>
</div>
@endsection

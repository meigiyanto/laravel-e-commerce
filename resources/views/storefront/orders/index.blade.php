@extends('layouts.storefront')

@section('title', 'My Orders')
@section('header', 'My Orders')

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
                    Orders
                </li>
            </ol>
        </nav>
    </div>
</div>

<section class="store-section">
    <div class="container">
        <div class="store-section-header">
            <div>
                <h1 class="store-section-title">My Orders</h1>
                <p class="store-section-subtitle">All of your orders are here</p>
            </div>
            @if ($orders->count() > 0)
                <a href="{{ route('storefront.shop') }}" class="btn btn-primary">
                    <em class="icon bi bi-cart"></em>
                    <span>Belanja Lagi</span>
                </a>
            @endif
        </div>
    </div>

    @if ($orders->count())
        <div class="container">
            <div class="card">
                <div class="card-body">
                    <table class="table table-striped table-bordered datatable-init">
                        <thead>
                            <tr>
                                <th>Order</th>
                                <th>Tanggal</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($orders as $order)
                                <tr>
                                    {{-- Order Number --}}
                                    <td>
                                        <div class="fw-bold">
                                            #{{ $order->order_number }}
                                        </div>
                                        <div class="text-soft small">
                                            {{ $order->items_count ?? $order->items()->count() }} item
                                        </div>
                                    </td>
                                    {{-- Date --}}
                                    <td>
                                        <div>{{ $order->created_at->format('d M Y') }}</div>
                                        <div class="text-soft small">{{ $order->created_at->format('H:i') }}</div>
                                    </td>
                                    {{-- Total --}}
                                    <td>
                                        <strong>Rp {{ number_format($order->total, 0, ',', '.') }}</strong>
                                    </td>
                                    {{-- Status --}}
                                    <td>
                                        @php
                                            $statusClass = match ($order->status) {
                                                'pending' => 'bg-warning text-dark p-2',
                                                'processing' => 'bg-info text-dark p-2',
                                                'shipped' => 'bg-primary p-2',
                                                'completed' => 'bg-success p-2',
                                                'cancelled' => 'bg-danger p-2',
                                                default => 'bg-secondary p-2',
                                            };
                                            $statusLabel = match ($order->status) {
                                                'pending' => 'Pending',
                                                'processing' => 'Processing',
                                                'shipped' => 'Shipped',
                                                'completed' => 'Completed',
                                                'cancelled' => 'Cancelled',
                                                default => ucfirst($order->status),
                                            };
                                        @endphp
                                        <span class="badge {{ $statusClass }}">{{ $statusLabel }}</span>
                                    </td>
                                    {{-- Action --}}
                                    <td class="text-end">
                                        <div class="btn-group">
                                            @if (
                                                $order->status === 'completed' &&
                                                $order->payment &&
                                                $order->payment->status === 'succeeded'
                                            )
                                                <form method="POST" action="{{ route('orders.refund.store', $order) }}" class="d-inline">
                                                    @csrf
                                                    <input type="hidden" name="amount" value="{{ $order->total }}">
                                                    <input type="hidden" name="reason" value="Refund order completed">
                                                    <button type="button" class="btn btn-outline-danger mr-2" onclick="return confirm('Ajukan refund untuk order #{{ $order->order_number }} sebesar Rp {{ number_format($order->total, 0, ',', '.') }}?')">Refund</button>
                                                </form>
                                            @endif
                                            <button type="button" class="btn btn-outline-primary js-order-detail" onclick="window.location.href='{{ route('orders.show', $order) }}'">Detail</button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @else
        <div class="alert alert-light border">
            <h2 class="title">Belum Ada Pesanan</h2>
            <p class="text-soft">Kamu belum memiliki riwayat pembelian.</p>
            <a href="{{ route('storefront.shop') }}" class="btn btn-primary"><i class="bi bi-cart"></i> Start Shopping</a>
        </div>
    @endif
</section>
@endsection

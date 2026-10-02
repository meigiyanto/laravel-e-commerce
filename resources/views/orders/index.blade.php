@extends('layouts.app')

@section('title', 'My Orders')

@section('header', 'My Orders')

@section('content')
<div class="nk-content-body">
    {{-- Header --}}
    <div class="nk-block-head nk-block-head-sm">
        <div class="nk-block-between">
            <div class="nk-block-head-content">
                <h3 class="nk-block-title page-title">My Orders</h3>
                <div class="nk-block-des text-soft">
                    <p>Lihat riwayat dan status pesanan kamu.</p>
                </div>
            </div>

            @if ($orders->count() > 0)
                <div class="nk-block-head-content">
                    <a href="{{ route('storefront.shop') }}" class="btn btn-primary">
                        <em class="icon ni ni-cart"></em>
                        <span>Belanja Lagi</span>
                    </a>
                </div>
            @endif
        </div>
    </div>

    {{-- Orders --}}
    <div class="nk-block">
        <div class="card card-bordered">
            <div class="card-inner">
                @if ($orders->count())
                    <div class="table-responsive">
                        <table class="table table-middle">
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
                                                    'pending' => 'bg-warning text-dark',
                                                    'processing' => 'bg-info text-dark',
                                                    'shipped' => 'bg-primary',
                                                    'completed' => 'bg-success',
                                                    'cancelled' => 'bg-danger',
                                                    default => 'bg-secondary',
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
                                            <a href="{{ route('orders.show', $order) }}" class="btn btn-sm btn-outline-primary">Detail</a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    {{-- Pagination --}}
                    @if ($orders->hasPages())
                        <div class="mt-4">
                            {{ $orders->links() }}
                        </div>
                    @endif
                @else
                    {{-- Empty --}}
                    <div class="text-center py-5">
                        <div class="mb-3">
                            <em class="icon ni ni-package" style="font-size: 48px;"></em>
                        </div>
                        <h5 class="title">Belum Ada Pesanan</h5>
                        <p class="text-soft">Kamu belum memiliki riwayat pembelian.</p>
                        <a href="{{ route('storefront.shop') }}" class="btn btn-primary">Mulai Belanja</a>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

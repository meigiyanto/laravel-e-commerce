@extends('layouts.app')

@section('title', 'Order #' . $order->order_number)

@section('header', 'Order Detail')

@section('content')
<div class="nk-content-body">
    {{-- Header --}}
    <div class="nk-block-head nk-block-head-sm">
        <div class="nk-block-between">
            <div class="nk-block-head-content">
                <h3 class="nk-block-title page-title">Order #{{ $order->order_number }}</h3>
                <div class="nk-block-des text-soft">
                    <p>Dibuat pada {{ $order->created_at->format('d M Y H:i') }}</p>
                </div>
            </div>

            <div class="nk-block-head-content">
                <a href="{{ route('orders.index') }}" class="btn btn-outline-secondary">Back to My Orders</a>
            </div>
        </div>
    </div>

    {{-- Status --}}
    <div class="nk-block">
        <div class="card card-bordered">
            <div class="card-inner">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <h5 class="title mb-1">Status Pesanan</h5>
                        <p class="text-soft mb-0">Status terbaru pesanan kamu.</p>
                    </div>

                    <div class="col-md-6 text-md-end mt-3 mt-md-0">
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

                        <span class="badge {{ $statusClass }}" style="font-size: 14px;">{{ $statusLabel }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Order Items --}}
    <div class="nk-block">
        <div class="card card-bordered">
            <div class="card-inner">
                <h5 class="title mb-4">Produk Pesanan</h5>

                <div class="table-responsive">
                    <table class="table table-middle">
                        <thead>
                            <tr>
                                <th>Produk</th>
                                <th>Harga</th>
                                <th>Qty</th>
                                <th class="text-end">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($order->items as $item)
                                <tr>
                                    {{-- Product --}}
                                    <td>
                                        <div class="d-flex align-items-center gap-3">
                                            @if ($item->product?->image)
                                                <img src="{{ asset('storage/' . $item->product->image) }}" alt="{{ $item->product_name }}" style="width: 60px; height: 60px; object-fit: cover; border-radius: 6px;">
                                            @else
                                                <div class="d-flex align-items-center justify-content-center bg-light" style="width: 60px; height: 60px; border-radius: 6px;">
                                                    <em class="icon ni ni-img"></em>
                                                </div>
                                            @endif
                                            <div>
                                                <div class="fw-bold">{{ $item->product_name }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    {{-- Price --}}
                                    <td>Rp {{ number_format($item->price, 0, ',', '.') }}
                                    </td>
                                    {{-- Quantity --}}
                                    <td>{{ $item->quantity }}</td>
                                    {{-- Subtotal --}}
                                    <td class="text-end">
                                        <strong class="text-end">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</strong>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                </div>
            </div>
        </div>
    </div>


    {{-- Bottom Information --}}
    <div class="row g-4">
        {{-- Shipping --}}
        <div class="col-lg-7">
            <div class="card card-bordered h-100">
                <div class="card-inner">
                    <h5 class="title mb-4">Informasi Pengiriman</h5>
                    <div class="mb-3">
                        <div class="text-soft small mb-1">Nama Penerima</div>
                        <strong>{{ $order->shipping_name }}</strong>
                    </div>

                    <div class="mb-3">
                        <div class="text-soft small mb-1">Nomor Telepon</div>
                        <strong>{{ $order->shipping_phone }}</strong>
                    </div>

                    <div>
                        <div class="text-soft small mb-1">Alamat</div>
                        <div>{{ $order->shipping_address }}</div>
                    </div>

                </div>
            </div>
        </div>

        {{-- Summary --}}
        <div class="col-lg-5">
            <div class="card card-bordered h-100">
                <div class="card-inner">
                    <h5 class="title mb-4">Ringkasan Pembayaran</h5>

                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-soft">Total Produk</span>
                        <span class="text-sort">Rp {{ number_format($order->items->sum('subtotal'), 0, ',', '.') }}</span>
                    </div>

                    <hr>

                    <div class="d-flex justify-content-between">
                        <strong>Total Pesanan</strong>
                        <strong class="text-primary">Rp {{ number_format($order->total_amount, 0, ',', '.') }}</strong>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>
@endsection

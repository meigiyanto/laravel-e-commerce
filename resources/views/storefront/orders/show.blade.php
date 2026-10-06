@extends('layouts.storefront')

@section('title', 'Order #' . $order->order_number)
@section('header', 'Order Detail')

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
                <li class="breadcrumb-item">
                    Order
                </li>
                <li class="breadcrumb-item active" aria-current="page">
                    Detail Order
                </li>
            </ol>
        </nav>
    </div>
</div>

<section class="store-section pb-3">
    <div class="container">

        <div class="store-section-header">
            <div>
                <h1 class="store-section-title">Order #{{ $order->order_number }}<h1>
                <p class="store-section-subtitle">Created at {{ $order->created_at->format('d M Y H:i') }}</p>
            </div>
        </div>

        <a href="{{ route('storefront.shop') }}" class="btn btn-outline-secondary">Continue shopping</a>

    </div>
</section>

{{-- Status --}}
<section>
    <div class="container">
        <div class="card">
            <div class="card-body">

                <div class="row align-items-center mb-3">
                    <div class="col-md-6">
                        <h5 class="title mb-1">Status Pesanan</h5>
                        <p class="text-soft mb-0">Status terbaru pesanan kamu.</p>
                    </div>
                    <div class="col-md-6 text-md-end mt-3 mt-md-0">
                        @php
                            $statusClass = match ($order->status) {
                                'pending' => 'bg-warning text-dark p-1',
                                'processing' => 'bg-info text-dark p-1',
                                'shipped' => 'bg-primary p-1',
                                'completed' => 'bg-success p-1',
                                'cancelled' => 'bg-danger p-1',
                                default => 'bg-secondary p-1',
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

                <h5>Produk Pesanan</h5>

                <table class="table table-middle table-striped my-3">
                    <thead>
                        <tr>
                            <th>Nama Produk</th>
                            <th>Harga</th>
                            <th>Qty</th>
                            <th class="text-end">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($order->items as $item)
                            @if ($order->status === 'completed' && $item->product)
                                <div class="my-2">
                                    <a href="{{ route('storefront.product', $item->product->slug) }}#review" class="btn btn-sm btn-outline-primary">
                                        <em class="icon ni ni-star"></em>
                                        Beri Review
                                    </a>
                                </div>
                            @endif
                            <tr>
                                {{-- Product --}}
                                <td>
                                    <div class="d-flex align-items-center gap-5">
                                    @if ($item->product?->image)
                                        <img
                                            src="{{ asset('storage/' . $item->product->image) }}"
                                            alt="{{ $item->product_name }}"
                                            style="width: 60px; height: 60px; object-fit: cover; border-radius: 6px;"
                                        >
                                    @else
                                        <div
                                            class="d-flex align-items-center justify-content-center bg-light"
                                            style="width: 40px; height: 40px; border-radius: 6px;"
                                        >
                                            <i class="bi bi-image text-muted"></i>
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

                <div class="row g-4">
                    {{-- Shipping --}}
                    <div class="col-lg-7 col-md-6">
                        <h5>Informasi Pengiriman</h5>
                        <div class="mb-3">
                            <div class="text-soft small mb-1">Nama Penerima</div>
                            <strong>{{ $order->customer_name }}</strong>
                        </div>

                        <div class="mb-3">
                            <div class="text-soft small mb-1">Nomor Telepon</div>
                            <strong>{{ $order->phone }}</strong>
                        </div>

                        <div>
                            <div class="text-soft small mb-1">Alamat</div>
                            <div>{{ $order->shipping_address }}</div>
                        </div>
                    </div>

                    {{-- Summary --}}
                    <div class="col-lg-5 col-md-6">
                        <h5>Ringkasan Pembayaran</h5>

                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-soft">Total Produk</span>
                            <span class="text-sort">Rp {{ number_format($order->items->sum('subtotal'), 0, ',', '.') }}</span>
                        </div>

                        <hr>

                        <div class="d-flex justify-content-between">
                            <strong>Total Pesanan</strong>
                            <strong class="text-primary">Rp {{ number_format($order->total, 0, ',', '.') }}</strong>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</section>
@endsection

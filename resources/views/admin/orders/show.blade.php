@extends('layouts.app')

@section('title', 'Order #' . $order->order_number)
@section('header', 'Order Detail')

@section('content')
<div class="nk-content-body">
    {{-- Header --}}
    <div class="nk-block-head nk-block-head-sm">
        <div class="nk-block-between">
            <div class="nk-block-head-content">
                <h3 class="nk-block-title page-title">
                    Order #{{ $order->order_number }}
                </h3>
                <div class="nk-block-des text-soft">
                    <p> Created on {{ $order->created_at->format('d M Y H:i') }}</p>
                </div>
            </div>


            <div class="nk-block-head-content">
                <a href="{{ route('admin.orders.index') }}" class="btn btn-outline-secondary">Go Back
                </a>
            </div>
        </div>
    </div>


    {{-- Flash --}}
    @if(session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif


    @if(session('error'))
        <div class="alert alert-danger">
            {{ session('error') }}
        </div>
    @endif


    <div class="row g-4">
        {{-- LEFT --}}
        <div class="col-lg-8">
            {{-- Order Items --}}
            <div class="card card-bordered mb-4">
                <div class="card-inner">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h5 class="title mb-0">Produk Pesanan</h5>                        <span class="text-soft">{{ $order->items->sum('quantity') }} item</span>
                    </div>

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
                                @foreach($order->items as $item)
                                    <tr>
                                        <td>
                                            <div class="fw-bold">{{ $item->product_name }}</div>
                                            <small class="text-soft">Product ID: {{ $item->product_id }}</small>
                                        </td>
                                        <td>Rp {{ number_format($item->price, 0, ',', '.') }}</td>
                                        <td>{{ $item->quantity }}</td>
                                        <td class="text-end"><string>Rp {{ number_format($item->subtotal, 0, ',', '.') }}</strong></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- Shipping --}}
            <div class="card card-bordered">
                <div class="card-inner">
                    <h5 class="title mb-4">Informasi Pengiriman</h5>
                    <div class="mb-3">
                        <div class="text-soft small">Nama Penerima</div>
                        <strong>{{ $order->customer_name }}</strong>
                    </div>

                    <div class="mb-3">
                        <div class="text-soft small">Nomor Telepon</div>
                        <strong>{{ $order->phone }}</strong>
                    </div>

                    <div class="mb-3">
                        <div class="text-soft small">Alamat</div>
                        <div>{{ $order->shipping_address }}</div>
                    </div>

                    @if($order->notes)
                        <div>
                            <div class="text-soft small">Catatan</div>
                            <div>{{ $order->notes }}</div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- RIGHT --}}
        <div class="col-lg-4">
            {{-- Status --}}
            <div class="card card-bordered mb-4">
                <div class="card-inner">
                    <h5 class="title mb-4">Update Status</h5>
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

                    <div class="mb-4">
                        <span class="badge {{ $statusClass }}" style="font-size: 14px;">{{ ucfirst($order->status) }}</span>
                    </div>

                    <form method="POST" action="{{ route('admin.orders.status', $order ) }}">
                        @csrf
                        @method('PATCH')

                        <select name="status" class="form-select mb-3" @disabled($order->status === 'cancelled')>
                            @foreach(['pending', 'processing', 'shipped', 'completed', 'cancelled' ] as $itemStatus)
                                <option
                                    value="{{ $itemStatus }}"
                                    @selected(
                                        $order->status === $itemStatus
                                    )
                                >
                                    {{ ucfirst($itemStatus) }}
                                </option>
                            @endforeach
                        </select>

                        @if($order->status === 'cancelled')
                            <div class="alert alert-warning">Order has been cancel cannot reactivate</div>
                        @else
                            <button type="submit" class="btn btn-primary w-100">Save Status</button>
                        @endif
                    </form>
                </div>
            </div>

            {{-- Customer --}}
            <div class="card card-bordered">
                <div class="card-inner">
                    <h5 class="title mb-4">Customer</h5>
                    <div class="mb-3">
                        <div class="text-soft small">Name</div>
                        <strong>{{ $order->user?->name ?? $order->customer_name }}</strong>
                    </div>
                    <div class="mb-3">
                        <div class="text-soft small">Email</div>
                        <div>{{ $order->user?->email ?? '-' }}</div>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-soft">Subtotal</span>
                        <span>Rp {{ number_format($order->subtotal, 0, ',', '.') }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-soft">Shipping</span>
                        <span>Rp {{ number_format($order->shipping_cost, 0, ',', '.') }}</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <strong>Total</strong>
                        <strong class="text-primary">Rp {{ number_format($order->total, 0, ',', '.') }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

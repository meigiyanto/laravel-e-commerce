@extends('layouts.storefront')

@section('title', 'My Orders')
@section('header', 'My Orders')

@section('content')
<section class="container py-4 py-lg-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="section-title mb-1">My Order</h1>
            <p class="text-muted mb-0">Compare product until 4 product</p>
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

    @if ($orders->count())
        <div>
            <table class="table table-striped table-middle js-datatable">
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
                                        <form
                                            method="POST"
                                            action="{{ route('orders.refund.store', $order) }}"
                                            class="d-inline"
                                        >
                                            @csrf

                                            <input
                                                type="hidden"
                                                name="amount"
                                                value="{{ $order->total }}"
                                            >

                                            <input
                                                type="hidden"
                                                name="reason"
                                                value="Refund order completed"
                                            >
                                            <button type="button" class="btn btn-outline-danger" onclick="return confirm('Ajukan refund untuk order #{{ $order->order_number }} sebesar Rp {{ number_format($order->total, 0, ',', '.') }}?')">Refund</button>
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
    @else
        <div class="alert alert-light border">
            <h2 class="title">Belum Ada Pesanan</h2>
            <p class="text-soft">Kamu belum memiliki riwayat pembelian.</p>
            <a href="{{ route('storefront.shop') }}" class="btn btn-primary"><i class="bi bi-cart"></i> Start Shopping</a>
        </div>
    @endif
</section>
@endsection

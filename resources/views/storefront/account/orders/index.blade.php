@extends('layouts.storefront')

@section('title', config('app.name') . ' - My Orders')

@section('content')
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
                <li class="breadcrumb-item">Account</li>
                <li class="breadcrumb-item active" aria-current="page">
                    My Orders
                </li>
            </ol>
        </nav>
    </div>
</div>

@include('components.account.page-header', [
    'title' => 'My Orders',
    'description' => 'View and manage your previous orders.',
    'eyebrow' => 'Order History',
        'action' => '<a href="' . route('storefront.shop') . '" class="store-btn-primary"><i class="bi bi-bag me-2"></i>Continue Shopping</a>',
])

<section class="pb-5">
    <div class="container">
        <div class="row g-4">
            {{-- Sidebar --}}
            <div class="col-lg-4">
                @include('components.account.sidebar')
            </div>
            <div class="col-lg-8">
                @if ($orders->count())
                    <div class="store-orders-summary mt-2">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="store-orders-summary-item">
                                <span class="store-orders-summary-icon">
                                    <i class="bi bi-receipt"></i>
                                </span>
            
                                <div>
                                    <span class="store-orders-summary-label">
                                        Total Orders
                                    </span>
            
                                    <strong>
                                        {{ $orders->total() }}
                                    </strong>
                                </div>
                            </div>
                            </div>
                            <div class="col-md-6">
                                <div class="store-orders-summary-item">
                                <span class="store-orders-summary-icon">
                                    <i class="bi bi-clock-history"></i>
                                </span>
            
                                <div>
                                    <span class="store-orders-summary-label">
                                        Showing
                                    </span>
            
                                    <strong>
                                        {{ $orders->count() }}
                                        {{ Str::plural('order', $orders->count()) }}
                                    </strong>
                                </div>
                            </div>
                            </div>
                        </div>
                    </div>
                    <div class="store-orders-list">
                        <div class="row">
                        @foreach ($orders as $order)
                            @php
                                $statusClass = match ($order->status) {
                                    'pending' => 'store-order-status-pending',
                                    'processing' => 'store-order-status-processing',
                                    'shipped' => 'store-order-status-shipped',
                                    'completed' => 'store-order-status-completed',
                                    'canceled' => 'store-order-status-canceled',
                                    default => 'store-order-status-default',
                                };
        
                                $statusLabel = match ($order->status) {
                                    'pending' => 'Pending',
                                    'processing' => 'Processing',
                                    'shipped' => 'Shipped',
                                    'completed' => 'Completed',
                                    'canceled' => 'canceled',
                                    default => ucfirst($order->status),
                                };
        
                                $itemCount = $order->items_count ?? $order->items()->count();
        
                                $canRefund = $order->status === 'completed' && $order->payment && $order->payment->status === 'succeeded';
                            @endphp
                                <div class="col-6">
                                    <article class="store-order-card">
                                        {{-- ORDER HEADER --}}
                                        <div class="store-order-card-header">
                                            <div class="store-order-reference">
                                                <span class="store-order-reference-icon">
                                                    <i class="bi bi-receipt"></i>
                                                </span>
                                                <div>
                                                    <span class="store-order-label">
                                                        Order
                                                    </span>
                
                                                    <strong>
                                                        #{{ $order->order_number }}
                                                    </strong>
                                                </div>
                                            </div>
                                            <span class="store-order-status {{ $statusClass }}">
                                                <span class="store-order-status-dot"></span>
                                                {{ $statusLabel }}
                                            </span>
                                        </div>
                
                                        {{-- ORDER BODY --}}
                                        <div class="store-order-card-body">
                
                                            <div class="store-order-meta">
                
                                                <div class="store-order-meta-item">
                                                    <span class="store-order-meta-icon">
                                                        <i class="bi bi-calendar3"></i>
                                                    </span>
                
                                                    <div>
                                                        <span>Date</span>
                                                        <strong>
                                                            {{ $order->created_at->format('d M Y') }}
                                                        </strong>
                                                    </div>
                                                </div>
                
                
                                                <div class="store-order-meta-item">
                                                    <span class="store-order-meta-icon">
                                                        <i class="bi bi-clock"></i>
                                                    </span>
                
                                                    <div>
                                                        <span>Time</span>
                                                        <strong>
                                                            {{ $order->created_at->format('H:i') }}
                                                        </strong>
                                                    </div>
                                                </div>
                
                
                                                <div class="store-order-meta-item">
                                                    <span class="store-order-meta-icon">
                                                        <i class="bi bi-box-seam"></i>
                                                    </span>
                
                                                    <div>
                                                        <span>Items</span>
                                                        <strong>
                                                            {{ $itemCount }}
                                                            {{ Str::plural('item', $itemCount) }}
                                                        </strong>
                                                    </div>
                                                </div>
                
                
                                                <div class="store-order-meta-item store-order-meta-total">
                                                    <span class="store-order-meta-icon">
                                                        <i class="bi bi-wallet2"></i>
                                                    </span>
                
                                                    <div>
                                                        <span>Total</span>
                                                        <strong>
                                                            Rp {{ number_format($order->total, 0, ',', '.') }}
                                                        </strong>
                                                    </div>
                                                </div>
                
                                            </div>
                
                                        </div>
                
                                        {{-- ORDER FOOTER --}}
                                        <div class="store-order-card-footer">
                                            <span class="store-order-created">
                                                Order placed
                                                {{ $order->created_at->diffForHumans() }}
                                            </span>
                
                                            <div class="store-order-actions">
                                                @if ($canRefund)
                                                    <form
                                                        method="POST"
                                                        action="{{ route('orders.refund.store', $order) }}"
                                                        class="store-order-refund-form"
                                                        data-order-refund
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
                
                                                        <button
                                                            type="submit"
                                                            class="store-order-refund-button"
                                                            onclick="return confirm('Ajukan refund untuk order #{{ $order->order_number }} sebesar Rp {{ number_format($order->total, 0, ',', '.') }}?')"
                                                        >
                                                            <i class="bi bi-arrow-counterclockwise"></i>
                                                            Refund
                                                        </button>
                                                    </form>
                
                                                @endif
                
                
                                                <a
                                                    href="{{ route('orders.show', $order) }}"
                                                    class="store-order-detail-button"
                                                >
                                                    View Details
                                                    <i class="bi bi-arrow-right"></i>
                                                </a>
                
                                            </div>
                                        </div>
                                    </article>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    {{-- PAGINATION --}}
                    @if ($orders->hasPages())
                        <div class="store-orders-pagination">
                            {{ $orders->links() }}
                        </div>
                    @endif
                @else
                    {{-- EMPTY STATE --}}
                    <div class="store-empty-state store-orders-empty">
                        <div class="store-empty-icon">
                            <i class="bi bi-receipt"></i>
                        </div>
        
                        <span class="store-section-eyebrow">
                            Order History
                        </span>
        
                        <h2>
                            No Orders Yet
                        </h2>
        
                        <p>
                            You haven't placed any orders yet.
                            Start shopping and your orders will appear here.
                        </p>
        
                        <a href="{{ route('storefront.shop') }}" class="store-btn-primary">
                            <i class="bi bi-bag me-2"></i>
                            Start Shopping
                        </a>
        
                    </div>
                @endif
            </div>
        </div>
    </div>
</section>
@endsection

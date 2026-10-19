@extends('layouts.storefront')

@section('title', config('app.name') . ' - Order #' . $order->order_number)

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

                <li class="breadcrumb-item">
                    <a href="{{ route('orders.index') }}">
                        Orders
                    </a>
                </li>

                <li class="breadcrumb-item active" aria-current="page">
                    #{{ $order->order_number }}
                </li>
            </ol>
        </nav>
    </div>
</div>

<section class="store-section store-order-detail-section">
    <div class="container">
        {{-- HEADER --}}
        <div class="store-order-detail-header">
            <div>
                <a
                    href="{{ route('orders.index') }}"
                    class="store-order-back-link"
                >
                    <i class="bi bi-arrow-left"></i>
                    Back to Orders
                </a>
                <span class="store-section-eyebrow">
                    Order Details
                </span>
                <h1 class="store-section-title">
                    #{{ $order->order_number }}
                </h1>
                <p class="store-section-subtitle">
                    Placed on
                    {{ $order->created_at->format('d M Y') }}
                    at
                    {{ $order->created_at->format('H:i') }}
                </p>
            </div>

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
            @endphp

            <div class="store-order-detail-status">
                <span class="store-order-status {{ $statusClass }}">
                    <span class="store-order-status-dot"></span>
                    {{ $statusLabel }}
                </span>
            </div>
        </div>

        {{-- STATUS TIMELINE --}}
        <div class="store-order-status-card">
            <div class="store-order-status-card-header">
                <div>
                    <h2>
                        Order Status
                    </h2>
                    <p>
                        Current status of your order.
                    </p>
                </div>

                <i class="bi bi-truck"></i>
            </div>

            @php
                $statusSteps = [
                    'pending' => [
                        'label' => 'Order Placed',
                        'icon' => 'bi-receipt',
                    ],
                    'processing' => [
                        'label' => 'Processing',
                        'icon' => 'bi-box-seam',
                    ],
                    'shipped' => [
                        'label' => 'Shipped',
                        'icon' => 'bi-truck',
                    ],
                    'completed' => [
                        'label' => 'Completed',
                        'icon' => 'bi-check-circle',
                    ],
                ];

                $statusOrder = [
                    'pending' => 1,
                    'processing' => 2,
                    'shipped' => 3,
                    'completed' => 4,
                ];

                $currentStep =
                    $statusOrder[$order->status] ?? 0;
            @endphp


            @if ($order->status === 'canceled')
                <div class="store-order-canceled-state">
                    <span>
                        <i class="bi bi-x-circle"></i>
                    </span>

                    <div>
                        <strong>Order canceled</strong>
                        <p>This order has been canceled.</p>
                    </div>
                </div>
            @else
                <div class="store-order-timeline">
                    @foreach ($statusSteps as $statusKey => $step)
                        @php
                            $stepNumber = $statusOrder[$statusKey];
                            $isCompleted = $currentStep >= $stepNumber;
                            $isCurrent = $currentStep === $stepNumber;
                        @endphp

                        <div
                            class="store-order-timeline-step
                                {{ $isCompleted ? 'is-completed' : '' }}
                                {{ $isCurrent ? 'is-current' : '' }}"
                        >

                            <span class="store-order-timeline-icon">
                                <i class="bi {{ $step['icon'] }}"></i>
                            </span>

                            <span class="store-order-timeline-label">
                                {{ $step['label'] }}
                            </span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>


        <div class="row g-4 align-items-start">

            {{-- LEFT --}}
            <div class="col-lg-8">

                {{-- PRODUCTS --}}
                <div class="store-order-panel">
                    <div class="store-order-panel-header">
                        <div>
                            <h2>
                                Ordered Products
                            </h2>

                            <p>
                                {{ $order->items->sum('quantity') }}
                                {{ Str::plural('item', $order->items->sum('quantity')) }}
                            </p>
                        </div>
                        <i class="bi bi-bag"></i>
                    </div>

                    <div class="store-order-products">
                        @foreach ($order->items as $item)
                            <article class="store-order-product">
                                <div class="store-order-product-image">
                                    @if ($item->product?->image)
                                        @php
                                            $image = $item->product->image;
                                            $imageUrl = filter_var($image, FILTER_VALIDATE_URL) ? $image : (Storage::disk('public')->exists($image) ? asset('storage/' . $image) : null);
                                        @endphp

                                        @if ($imageUrl)

                                            <img
                                                src="{{ $imageUrl }}"
                                                alt="{{ $item->product_name }}"
                                                loading="lazy"
                                            >

                                        @else
                                            <span>
                                                <i class="bi bi-image"></i>
                                            </span>
                                        @endif
                                    @else
                                        <span>
                                            <i class="bi bi-image"></i>
                                        </span>
                                    @endif
                                </div>

                                <div class="store-order-product-content">
                                    <div class="store-order-product-main">
                                        <h3>{{ $item->product_name }}</h3>
                                        <span>Rp {{ number_format($item->price, 0, ',', '.') }} × {{ $item->quantity }}</span>
                                    </div>

                                    <div class="store-order-product-total">
                                        <strong>Rp {{ number_format($item->subtotal, 0, ',', '.') }}</strong>
                                        @if ($order->status === 'completed' && $item->product)
                                            <a
                                                href="{{ route('storefront.product', $item->product->slug) }}#review"
                                                class="store-order-review-button"
                                            >
                                                <i class="bi bi-star"></i>
                                                Review
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            </article>
                        @endforeach
                    </div>
                </div>


                {{-- SHIPPING --}}
                <div class="store-order-panel">
                    <div class="store-order-panel-header">
                        <div>
                            <h2>Shipping Information</h2>
                            <p>Delivery details for this order.</p>
                        </div>
                        <i class="bi bi-geo-alt"></i>
                    </div>


                    <div class="store-order-shipping">
                        <div class="store-order-shipping-item">
                            <span>
                                <i class="bi bi-person"></i>
                            </span>

                            <div>
                                <small>Recipient</small>
                                <strong>{{ $order->customer_name }}</strong>
                            </div>

                        </div>


                        <div class="store-order-shipping-item">
                            <span>
                                <i class="bi bi-telephone"></i>
                            </span>

                            <div>
                                <small>Phone</small>
                                <strong>{{ $order->phone }}</strong>
                            </div>
                        </div>

                        <div class="store-order-shipping-item">
                            <span>
                                <i class="bi bi-geo-alt"></i>
                            </span>

                            <div>
                                <small>Shipping Address</small>
                                <strong>{{ $order->shipping_address }}</strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>


            {{-- RIGHT --}}
            <div class="col-lg-4">
                <aside class="store-order-summary">
                    <div class="store-order-summary-header">
                        <h2>Order Summary</h2>
                        <i class="bi bi-receipt"></i>
                    </div>

                    <div class="store-order-summary-body">
                        <div class="store-order-summary-row">
                            <span>Products</span>
                            <strong>Rp {{ number_format($order->subtotal, 0, ',', '.') }}</strong>
                        </div>

                        <div class="store-order-summary-row">
                            <span>Shipping</span>
                            <strong>Rp {{ number_format($order->shipping_cost, 0, ',', '.') }}</strong>
                        </div>

                        <div class="store-order-summary-divider"></div>

                        <div class="store-order-summary-total">
                            <span>Total</span>
                            <strong>Rp {{ number_format($order->total, 0, ',', '.') }}</strong>
                        </div>

                        @if ($order->payment)
                            <div class="store-order-payment-status">
                                <span>Payment</span>
                                <strong>{{ ucfirst($order->payment->status) }}</strong>
                            </div>
                        @endif

                        @php($pendingCancellationRequest = $order->cancellationRequests->firstWhere('status', 'pending'))
                        
                        @if($pendingCancellationRequest)
                            <div class="alert alert-warning mt-3" role="status">
                                Permintaan pembatalan sedang ditinjau admin.
                            </div>
                        @elseif($order->status === 'pending' && !$order->cancellationRequests->contains('status', 'approved'))
                            <form method="POST" action="{{ route('orders.cancellation-requests.store', $order) }}" class="store-order-detail-cancel-form mt-3">
                                @csrf
                                <label for="cancellation_reason_code" class="form-label">Mengapa Anda ingin membatalkan pesanan?</label>
                                <select id="cancellation_reason_code" name="reason_code" class="form-select" required>
                                    <option value="">Pilih alasan pembatalan</option>
                                    <option value="changed_mind" @selected(old('reason_code') === 'changed_mind')>Berubah pikiran</option>
                                    <option value="wrong_item" @selected(old('reason_code') === 'wrong_item')>Produk atau varian yang dipilih salah</option>
                                    <option value="wrong_address" @selected(old('reason_code') === 'wrong_address')>Alamat pengiriman salah</option>
                                    <option value="payment_issue" @selected(old('reason_code') === 'payment_issue')>Ada masalah saat pembayaran</option>
                                    <option value="delivery_too_long" @selected(old('reason_code') === 'delivery_too_long')>Estimasi pengiriman terlalu lama</option>
                                    <option value="duplicate_order" @selected(old('reason_code') === 'duplicate_order')>Pesanan dibuat lebih dari satu kali</option>
                                    <option value="other" @selected(old('reason_code') === 'other')>Alasan lainnya</option>
                                </select>
                                
                                @error('reason_code') 
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                                
                                @error('reason') 
                                    <div class="alert alert-danger mt-2">{{ $message }}</div> 
                                @enderror
                        
                                <label for="cancellation_reason_details" class="form-label mt-3">Keterangan tambahan <span class="text-muted">(opsional, wajib untuk “Alasan lainnya”)</span></label>
                                <textarea id="cancellation_reason_details" name="reason_details" class="form-control" rows="3" maxlength="1500" placeholder="Jelaskan jika ada detail yang perlu diketahui admin">{{ old('reason_details') }}</textarea>
                                
                                @error('reason_details') 
                                    <div class="text-danger small mt-1">{{ $message }}</div> 
                                @enderror
                                
                                <button type="submit" class="store-order-cancel-button mt-3" onclick="return confirm('Kirim permintaan pembatalan untuk ditinjau admin? Pesanan tidak langsung dibatalkan.')">
                                    <i class="bi bi-x-circle"></i> Ajukan Pembatalan
                                </button>
                                <p class="small text-muted mt-2 mb-0">Pesanan tetap aktif sampai admin meninjau permintaan Anda.</p>
                            </form>
                        @endif
                        
                        @if ($order->status === 'completed' && $order->payment && $order->payment->status === 'succeeded')
                            <form
                                method="POST"
                                action="{{ route('orders.refund.store', $order) }}"
                                class="store-order-detail-refund-form"
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
                                    class="store-order-refund-button store-order-refund-button-full"
                                    onclick="return confirm('Ajukan refund untuk order #{{ $order->order_number }} sebesar Rp {{ number_format($order->total, 0, ',', '.') }}?')"
                                >
                                    <i class="bi bi-arrow-counterclockwise"></i>
                                    Request Refund
                                </button>
                            </form>
                        @endif

                        <a
                            href="{{ route('storefront.shop') }}"
                            class="store-order-continue-button"
                        >
                            <i class="bi bi-bag"></i>
                            Continue Shopping
                        </a>
                    </div>
                </aside>
            </div>
        </div>
    </div>
</section>
@endsection

@extends('layouts.app')

@section('title', 'Refund #' . $refund->id)
@section('header', 'Refund Detail')

@section('content')
<div class="nk-content-body">

    {{-- Header --}}
    <div class="nk-block-head nk-block-head-sm">
        <div class="nk-block-between">
            <div class="nk-block-head-content">
                <h3 class="nk-block-title page-title">
                    Refund #{{ $refund->id }}
                </h3>

                <div class="nk-block-des text-soft">
                    <p>
                        Pengajuan refund
                        @if($refund->order)
                            untuk Order #{{ $refund->order->order_number }}
                        @endif
                    </p>
                </div>
            </div>

            <div class="nk-block-head-content">
                <a
                    href="{{ route('admin.refunds.index') }}"
                    class="btn btn-outline-secondary"
                >
                    Go Back
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

    @php
        $statusClass = match ($refund->status) {
            'requested' => 'bg-warning text-dark',
            'approved' => 'bg-primary',
            'processing' => 'bg-info text-dark',
            'completed' => 'bg-success',
            'rejected',
            'failed' => 'bg-danger',
            default => 'bg-secondary',
        };

        $statusLabel = match ($refund->status) {
            'requested' => 'Requested',
            'approved' => 'Approved',
            'processing' => 'Processing',
            'completed' => 'Completed',
            'rejected' => 'Rejected',
            'failed' => 'Failed',
            default => ucfirst($refund->status),
        };
    @endphp

    <div class="row g-4">

        {{-- LEFT --}}
        <div class="col-lg-8">

            {{-- Refund Information --}}
            <div class="card card-bordered mb-4">
                <div class="card-inner">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h5 class="title mb-0">
                            Refund Information
                        </h5>

                        <span class="badge {{ $statusClass }}">
                            {{ $statusLabel }}
                        </span>
                    </div>

                    <div class="row g-4">

                        <div class="col-md-6">
                            <div class="text-soft small">
                                Refund ID
                            </div>

                            <strong>
                                #{{ $refund->id }}
                            </strong>
                        </div>

                        <div class="col-md-6">
                            <div class="text-soft small">
                                Order
                            </div>

                            @if($refund->order)
                                <a
                                    href="{{ route(
                                        'admin.orders.show',
                                        $refund->order
                                    ) }}"
                                >
                                    <strong>
                                        #{{ $refund->order->order_number }}
                                    </strong>
                                </a>
                            @else
                                -
                            @endif
                        </div>

                        <div class="col-md-6">
                            <div class="text-soft small">
                                Nominal Refund
                            </div>

                            <strong class="text-primary">
                                Rp {{ number_format(
                                    $refund->amount,
                                    0,
                                    ',',
                                    '.'
                                ) }}
                            </strong>
                        </div>

                        <div class="col-md-6">
                            <div class="text-soft small">
                                Provider
                            </div>

                            <strong>
                                {{ ucfirst($refund->provider ?? '-') }}
                            </strong>
                        </div>

                        <div class="col-md-6">
                            <div class="text-soft small">
                                Requested At
                            </div>

                            <span>
                                {{ optional($refund->requested_at)
                                    ->format('d M Y H:i') ?? '-' }}
                            </span>
                        </div>

                        <div class="col-md-6">
                            <div class="text-soft small">
                                Processed At
                            </div>

                            <span>
                                {{ optional($refund->processed_at)
                                    ->format('d M Y H:i') ?? '-' }}
                            </span>
                        </div>

                    </div>

                    <hr>

                    <div>
                        <div class="text-soft small mb-1">
                            Alasan Refund
                        </div>

                        <div>
                            {{ $refund->reason ?: '-' }}
                        </div>
                    </div>

                    @if($refund->reference_id)
                        <hr>

                        <div>
                            <div class="text-soft small mb-1">
                                Provider Reference
                            </div>

                            <code>
                                {{ $refund->reference_id }}
                            </code>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Order Information --}}
            @if($refund->order)
                <div class="card card-bordered">
                    <div class="card-inner">
                        <h5 class="title mb-4">
                            Order Information
                        </h5>

                        <div class="row g-4">

                            <div class="col-md-6">
                                <div class="text-soft small">
                                    Customer
                                </div>

                                <strong>
                                    {{ $refund->order->customer_name }}
                                </strong>
                            </div>

                            <div class="col-md-6">
                                <div class="text-soft small">
                                    Phone
                                </div>

                                <strong>
                                    {{ $refund->order->phone }}
                                </strong>
                            </div>

                            <div class="col-md-6">
                                <div class="text-soft small">
                                    Order Status
                                </div>

                                <strong>
                                    {{ ucfirst($refund->order->status) }}
                                </strong>
                            </div>

                            <div class="col-md-6">
                                <div class="text-soft small">
                                    Order Total
                                </div>

                                <strong>
                                    Rp {{ number_format(
                                        $refund->order->total,
                                        0,
                                        ',',
                                        '.'
                                    ) }}
                                </strong>
                            </div>

                            <div class="col-12">
                                <div class="text-soft small">
                                    Shipping Address
                                </div>

                                <div>
                                    {{ $refund->order->shipping_address }}
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            @endif

        </div>

        {{-- RIGHT --}}
        <div class="col-lg-4">

            {{-- Payment --}}
            <div class="card card-bordered mb-4">
                <div class="card-inner">
                    <h5 class="title mb-4">
                        Payment
                    </h5>

                    @if($refund->payment)
                        <div class="mb-3">
                            <div class="text-soft small">
                                Status
                            </div>

                            <strong>
                                {{ ucfirst($refund->payment->status) }}
                            </strong>
                        </div>

                        <div class="mb-3">
                            <div class="text-soft small">
                                Gross Amount
                            </div>

                            <strong>
                                Rp {{ number_format(
                                    $refund->payment->gross_amount,
                                    0,
                                    ',',
                                    '.'
                                ) }}
                            </strong>
                        </div>

                        <div class="mb-3">
                            <div class="text-soft small">
                                Payment Method
                            </div>

                            <strong>
                                {{ ucfirst(
                                    $refund->payment->payment_method
                                    ?? '-'
                                ) }}
                            </strong>
                        </div>

                        <div>
                            <div class="text-soft small">
                                Provider
                            </div>

                            <strong>
                                {{ ucfirst(
                                    $refund->payment->provider
                                    ?? '-'
                                ) }}
                            </strong>
                        </div>
                    @else
                        <div class="alert alert-warning mb-0">
                            Payment tidak ditemukan.
                        </div>
                    @endif
                </div>
            </div>

            {{-- Action placeholder --}}
            <div class="card card-bordered">
                <div class="card-inner">
                    <h5 class="title mb-3">
                        Refund Action
                    </h5>

                    @if($refund->status === \App\Models\Refund::STATUS_REQUESTED)
                        <div class="alert alert-info">
                            Refund ini menunggu tindakan admin.
                        </div>

                        <p class="text-soft mb-0">
                            Tombol Process Refund dan Reject Refund
                            akan ditambahkan pada tahap berikutnya.
                        </p>
                    @else
                        <div class="alert alert-secondary mb-0">
                            Refund ini sudah memiliki status
                            <strong>{{ $statusLabel }}</strong>.
                        </div>
                    @endif
                </div>
            </div>

        </div>
    </div>

</div>
@endsection
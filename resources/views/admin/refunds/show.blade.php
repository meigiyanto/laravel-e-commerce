@extends('layouts.admin')

@section('title', 'Refund #' . $refund->id)
@section('header', 'Refund Detail')

@section('content')
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
            \App\Models\Refund::STATUS_REQUESTED =>
                'bg-warning text-dark',

            \App\Models\Refund::STATUS_APPROVED =>
                'bg-primary',

            \App\Models\Refund::STATUS_PROCESSING =>
                'bg-info text-dark',

            \App\Models\Refund::STATUS_COMPLETED =>
                'bg-success',

            \App\Models\Refund::STATUS_REJECTED,
            \App\Models\Refund::STATUS_FAILED =>
                'bg-danger',

            default =>
                'bg-secondary',
        };

        $statusLabel = match ($refund->status) {
            \App\Models\Refund::STATUS_REQUESTED =>
                'Requested',

            \App\Models\Refund::STATUS_APPROVED =>
                'Approved',

            \App\Models\Refund::STATUS_PROCESSING =>
                'Processing',

            \App\Models\Refund::STATUS_COMPLETED =>
                'Completed',

            \App\Models\Refund::STATUS_REJECTED =>
                'Rejected',

            \App\Models\Refund::STATUS_FAILED =>
                'Failed',

            default =>
                ucfirst($refund->status),
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

                        {{-- Refund ID --}}
                        <div class="col-md-6">
                            <div class="text-soft small">
                                Refund ID
                            </div>

                            <strong>
                                #{{ $refund->id }}
                            </strong>
                        </div>

                        {{-- Order --}}
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

                        {{-- Amount --}}
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

                        {{-- Provider --}}
                        <div class="col-md-6">
                            <div class="text-soft small">
                                Provider
                            </div>

                            <strong>
                                {{ ucfirst($refund->provider ?? '-') }}
                            </strong>
                        </div>

                        {{-- Requested --}}
                        <div class="col-md-6">
                            <div class="text-soft small">
                                Requested At
                            </div>

                            <span>
                                {{ optional($refund->requested_at)
                                    ->format('d M Y H:i') ?? '-' }}
                            </span>
                        </div>

                        {{-- Processed --}}
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

                    {{-- Reason --}}
                    <div>
                        <div class="text-soft small mb-1">
                            Alasan Refund
                        </div>

                        <div>
                            {{ $refund->reason ?: '-' }}
                        </div>
                    </div>

                    {{-- Provider Reference --}}
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

            {{-- Provider Result --}}
            @if($refund->status === \App\Models\Refund::STATUS_COMPLETED)

                <div class="alert alert-success mb-4">
                    <div class="fw-bold mb-1">
                        Refund berhasil diselesaikan
                    </div>

                    <div>
                        Dana refund telah berhasil diproses oleh
                        <strong>
                            {{ ucfirst($refund->provider ?? '-') }}
                        </strong>.
                    </div>

                    @if($refund->reference_id)
                        <div class="mt-2">
                            Reference:
                            <code>{{ $refund->reference_id }}</code>
                        </div>
                    @endif
                </div>

            @elseif($refund->status === \App\Models\Refund::STATUS_PROCESSING)

                <div class="alert alert-info mb-4">
                    <div class="fw-bold mb-1">
                        Refund sedang diproses
                    </div>

                    <div>
                        Provider
                        <strong>
                            {{ ucfirst($refund->provider ?? '-') }}
                        </strong>
                        masih memproses refund ini.
                    </div>

                    @if($refund->reference_id)
                        <div class="mt-2">
                            Reference:
                            <code>{{ $refund->reference_id }}</code>
                        </div>
                    @endif
                </div>

            @elseif($refund->status === \App\Models\Refund::STATUS_FAILED)

                <div class="alert alert-danger mb-4">
                    <div class="fw-bold mb-1">
                        Refund gagal diproses
                    </div>

                    <div>
                        Provider
                        <strong>
                            {{ ucfirst($refund->provider ?? '-') }}
                        </strong>
                        gagal menyelesaikan refund ini.
                    </div>

                    @if(data_get($refund->metadata, 'error'))
                        <div class="mt-2">
                            <div class="text-soft small mb-1">
                                Error
                            </div>

                            <code>
                                {{ data_get($refund->metadata, 'error') }}
                            </code>
                        </div>
                    @endif
                </div>

            @elseif($refund->status === \App\Models\Refund::STATUS_REJECTED)

                <div class="alert alert-danger mb-4">
                    <div class="fw-bold mb-1">
                        Refund ditolak
                    </div>

                    <div>
                        Pengajuan refund ini telah ditolak dan
                        tidak dapat diproses kembali.
                    </div>
                </div>

            @elseif($refund->status === \App\Models\Refund::STATUS_APPROVED)

                <div class="alert alert-primary mb-4">
                    <div class="fw-bold mb-1">
                        Refund disetujui
                    </div>

                    <div>
                        Refund telah disetujui dan tidak lagi
                        menunggu tindakan admin.
                    </div>
                </div>

            @endif

            {{-- Order Information --}}
            @if($refund->order)

                <div class="card card-bordered">
                    <div class="card-inner">

                        <h5 class="title mb-4">
                            Order Information
                        </h5>

                        <div class="row g-4">

                            {{-- Customer --}}
                            <div class="col-md-6">
                                <div class="text-soft small">
                                    Customer
                                </div>

                                <strong>
                                    {{ $refund->order->customer_name }}
                                </strong>
                            </div>

                            {{-- Phone --}}
                            <div class="col-md-6">
                                <div class="text-soft small">
                                    Phone
                                </div>

                                <strong>
                                    {{ $refund->order->phone }}
                                </strong>
                            </div>

                            {{-- Order Status --}}
                            <div class="col-md-6">
                                <div class="text-soft small">
                                    Order Status
                                </div>

                                <strong>
                                    {{ ucfirst($refund->order->status) }}
                                </strong>
                            </div>

                            {{-- Order Total --}}
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

                            {{-- Shipping --}}
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

            {{-- Refund Action --}}
            <div class="card card-bordered">
                <div class="card-inner">

                    <h5 class="title mb-3">
                        Refund Action
                    </h5>

                    @if($refund->status === \App\Models\Refund::STATUS_REQUESTED)

                        <div class="alert alert-warning">
                            <div class="fw-bold mb-1">
                                Menunggu tindakan admin
                            </div>

                            <div class="small">
                                Pastikan nominal, alasan, order,
                                dan informasi pembayaran sudah benar
                                sebelum mengambil tindakan.
                            </div>
                        </div>

                        <div class="d-grid gap-2">

                            {{-- Process --}}
                            <form
                                method="POST"
                                action="{{ route(
                                    'admin.refunds.process',
                                    $refund
                                ) }}"
                                onsubmit="
                                    return confirm(
                                        'Anda akan memproses refund sebesar Rp {{ number_format($refund->amount, 0, ',', '.') }} melalui {{ ucfirst($refund->provider ?? '-') }}. Setelah diproses, refund tidak dapat dikembalikan ke status Requested. Lanjutkan?'
                                    );
                                "
                            >
                                @csrf

                                <button
                                    type="submit"
                                    class="btn btn-primary w-100"
                                >
                                    Process Refund
                                </button>
                            </form>

                            {{-- Reject --}}
                            <form
                                method="POST"
                                action="{{ route(
                                    'admin.refunds.reject',
                                    $refund
                                ) }}"
                                onsubmit="
                                    return confirm(
                                        'Anda akan menolak refund ini. Refund yang ditolak tidak dapat diproses kembali. Lanjutkan?'
                                    );
                                "
                            >
                                @csrf

                                <button
                                    type="submit"
                                    class="btn btn-outline-danger w-100"
                                >
                                    Reject Refund
                                </button>
                            </form>

                        </div>

                    @elseif($refund->status === \App\Models\Refund::STATUS_COMPLETED)

                        <div class="alert alert-success mb-0">
                            Refund sudah selesai diproses.

                            @if($refund->reference_id)
                                <div class="mt-2">
                                    Provider Reference:
                                    <code>
                                        {{ $refund->reference_id }}
                                    </code>
                                </div>
                            @endif
                        </div>

                    @elseif($refund->status === \App\Models\Refund::STATUS_PROCESSING)

                        <div class="alert alert-info mb-0">
                            Refund masih diproses oleh provider.

                            @if($refund->reference_id)
                                <div class="mt-2">
                                    Provider Reference:
                                    <code>
                                        {{ $refund->reference_id }}
                                    </code>
                                </div>
                            @endif
                        </div>

                    @elseif($refund->status === \App\Models\Refund::STATUS_FAILED)

                        <div class="alert alert-danger mb-0">
                            Refund gagal diproses.

                            @if(data_get($refund->metadata, 'error'))
                                <div class="mt-2">
                                    <strong>Error:</strong>

                                    <div class="mt-1">
                                        {{ data_get(
                                            $refund->metadata,
                                            'error'
                                        ) }}
                                    </div>
                                </div>
                            @endif
                        </div>

                    @elseif($refund->status === \App\Models\Refund::STATUS_REJECTED)

                        <div class="alert alert-danger mb-0">
                            Refund telah ditolak.

                            <div class="mt-1">
                                Refund ini tidak dapat diproses kembali.
                            </div>
                        </div>

                    @elseif($refund->status === \App\Models\Refund::STATUS_APPROVED)

                        <div class="alert alert-primary mb-0">
                            Refund telah disetujui.

                            <div class="mt-1">
                                Tidak diperlukan tindakan admin lebih lanjut.
                            </div>
                        </div>

                    @else

                        <div class="alert alert-secondary mb-0">
                            Refund ini memiliki status
                            <strong>{{ $statusLabel }}</strong>.
                            Tidak ada tindakan yang tersedia.
                        </div>

                    @endif

                </div>
            </div>

        </div>
    </div>
@endsection

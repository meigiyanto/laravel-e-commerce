@extends('layouts.admin')

@section('title', 'Refund Management')
@section('header', 'Refund Management')

@section('content')
    {{-- Header --}}
    <div class="nk-block-head nk-block-head-sm">
        <div class="nk-block-between">
            <div class="nk-block-head-content">
                <h3 class="nk-block-title page-title">
                    Refund Management
                </h3>

                <div class="nk-block-des text-soft">
                    <p>
                        Kelola dan pantau pengajuan refund customer.
                    </p>
                </div>
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

    {{-- Status Summary --}}
    <div class="row g-3 mb-4">
        @foreach($statuses as $itemStatus)
            @php
                $count = $statusCounts[$itemStatus] ?? 0;

                $statusClass = match ($itemStatus) {
                    'requested' => 'text-warning',
                    'approved' => 'text-primary',
                    'processing' => 'text-info',
                    'completed' => 'text-success',
                    'rejected' => 'text-danger',
                    'failed' => 'text-danger',
                    default => 'text-soft',
                };

                $statusLabel = match ($itemStatus) {
                    'requested' => 'Requested',
                    'approved' => 'Approved',
                    'processing' => 'Processing',
                    'completed' => 'Completed',
                    'rejected' => 'Rejected',
                    'failed' => 'Failed',
                    default => ucfirst($itemStatus),
                };
            @endphp

            <div class="col-6 col-md-4 col-xl">
                <a
                    href="{{ route('admin.refunds.index', [
                        'status' => $itemStatus,
                        'search' => $search,
                    ]) }}"
                    class="card card-bordered h-100"
                >
                    <div class="card-inner">
                        <span class="text-soft">
                            {{ $statusLabel }}
                        </span>

                        <div class="fs-2 fw-bold mt-1 {{ $statusClass }}">
                            {{ $count }}
                        </div>
                    </div>
                </a>
            </div>
        @endforeach
    </div>

    {{-- Refund List --}}
    <div class="card card-bordered">
        <div class="card-inner">

            {{-- Search & Filter --}}
            <form
                method="GET"
                action="{{ route('admin.refunds.index') }}"
                class="row g-2 mb-4"
            >
                <div class="col-md-6">
                    <input
                        type="text"
                        name="search"
                        value="{{ $search }}"
                        class="form-control"
                        placeholder="Cari nomor order, customer, atau email"
                    >
                </div>

                <div class="col-md-3">
                    <select
                        name="status"
                        class="form-select"
                    >
                        <option value="">
                            All Status
                        </option>

                        @foreach($statuses as $itemStatus)
                            <option
                                value="{{ $itemStatus }}"
                                @selected($status === $itemStatus)
                            >
                                {{ ucfirst($itemStatus) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3">
                    <button
                        type="submit"
                        class="btn btn-primary flex-grow-1"
                    >
                        Filter
                    </button>

                    @if($search || $status)
                        <button
                            type="button"
                            class="btn btn-secondary flex-grow-1"
                            onclick="window.location.href={{ route('admin.refunds.index') }}"
                        >
                            Reset
                        </button>
                        {{--
                        <a
                            href="{{ route('admin.refunds.index') }}"
                            class="btn btn-secondary"
                        >
                            Reset
                        </a>
                        --}}
                    @endif
                </div>
            </form>

            {{-- Table --}}
            <div class="table-responsive">
                <table class="table table-middle">
                    <thead>
                        <tr>
                            <th>Order</th>
                            <th>Customer</th>
                            <th>Nominal</th>
                            <th>Provider</th>
                            <th>Status</th>
                            <th>Tanggal</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse($refunds as $refund)
                            @php
                                $statusClass = match ($refund->status) {
                                    'requested' => 'bg-warning text-dark',
                                    'approved' => 'bg-primary',
                                    'processing' => 'bg-info text-dark',
                                    'completed' => 'bg-success',
                                    'rejected' => 'bg-danger',
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

                            <tr>
                                {{-- Order --}}
                                <td>
                                    @if($refund->order)
                                        <strong>
                                            #{{ $refund->order->order_number }}
                                        </strong>
                                    @else
                                        -
                                    @endif
                                </td>

                                {{-- Customer --}}
                                <td>
                                    @if($refund->order)
                                        <div class="fw-bold">
                                            {{ $refund->order->customer_name }}
                                        </div>

                                        <small class="text-soft">
                                            {{ $refund->order->user?->email ?? '-' }}
                                        </small>
                                    @else
                                        -
                                    @endif
                                </td>

                                {{-- Amount --}}
                                <td>
                                    <strong>
                                        Rp {{ number_format(
                                            $refund->amount,
                                            0,
                                            ',',
                                            '.'
                                        ) }}
                                    </strong>
                                </td>

                                {{-- Provider --}}
                                <td>
                                    {{ ucfirst($refund->provider ?? '-') }}
                                </td>

                                {{-- Status --}}
                                <td>
                                    <span class="badge {{ $statusClass }}">
                                        {{ $statusLabel }}
                                    </span>
                                </td>

                                {{-- Date --}}
                                <td>
                                    {{ optional($refund->requested_at ?? $refund->created_at)
                                        ->format('d M Y H:i') }}
                                </td>

                                {{-- Action --}}
                                <td class="text-end">
                                    <a
                                        href="{{ route('admin.refunds.show', $refund) }}"
                                        class="btn btn-sm btn-outline-primary"
                                    >
                                        Detail
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td
                                    colspan="7"
                                    class="text-center text-soft py-4"
                                >
                                    Belum ada pengajuan refund.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            @if($refunds->hasPages())
                <div class="mt-4">
                    {{ $refunds->links() }}
                </div>
            @endif

        </div>
    </div>
@endsection

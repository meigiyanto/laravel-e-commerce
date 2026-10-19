@extends('layouts.admin')

@section('title', 'Tinjau Permintaan Pembatalan')
@section('header', 'Tinjau Permintaan Pembatalan')

@section('content')
@php($order = $cancellationRequest->order)
<div class="nk-block-head nk-block-head-sm">
    <div class="nk-block-between">
        <div class="nk-block-head-content">
            <h3 class="nk-block-title page-title">Permintaan Pembatalan</h3>
            <p>Detail order: <strong>#{{ $order?->order_number ?? $cancellationRequest->order_id }}</strong></p>
        </div>
        <div>
            <a href="{{ route('admin.order-cancellation-requests.index') }}" class="btn btn-outline-secondary"><em class="icon ni ni-arrow-left"></em> Kembali</a>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-md-7">
        <div class="card card-bordered">
            <div class="card-inner">
                <h5>Detail Permintaan</h5>
                <p>
                    <strong>Customer:</strong> {{ $cancellationRequest->user?->name }} ({{ $cancellationRequest->user?->email }})
                </p>
                <p>
                    <strong>Status pesanan:</strong> {{ ucfirst($order?->status ?? '-') }}
                </p>
                <p>
                    <strong>Status permintaan:</strong> {{ ucfirst($cancellationRequest->status) }}
                </p>
                <p>
                    <strong>Alasan customer:</strong>
                </p>
                <div class="p-3 bg-lighter rounded">
                    {{ $cancellationRequest->reason }}
                </div>
                @if($cancellationRequest->admin_note)<p class="mt-3">
                    <strong>Catatan admin:</strong> {{ $cancellationRequest->admin_note }}
                </p>
                @endif
            @if($cancellationRequest->status === 'pending')
                <hr>
                
                <form method="POST" action="{{ route('admin.order-cancellation-requests.decide', $cancellationRequest) }}">
                    @csrf
                    <div class="form-group">
                        <label class="form-label" for="admin_note">Catatan keputusan (opsional)</label>
                        <textarea class="form-control mb-3" id="admin_note" name="admin_note" rows="3" maxlength="2000">{{ old('admin_note') }}</textarea>
                    </div>
                    <div class="d-flex flex-wrap gap-2 mb-3">
                        <button class="btn btn-success" name="decision" value="approved" type="submit" onclick="return confirm('Setujui pembatalan? Stok akan dipulihkan jika pesanan masih memenuhi syarat.')">Setujui Pembatalan</button>
                        <button class="btn btn-danger" name="decision" value="rejected" type="submit" onclick="return confirm('Tolak permintaan pembatalan ini?')">Tolak Permintaan</button>
                    </div>
                </form>
            @else
                <p class="text-soft mt-3">
                    Ditinjau oleh {{ $cancellationRequest->reviewer?->name ?? 'Admin' }} pada {{ $cancellationRequest->reviewed_at?->format('d M Y H:i') ?? '-' }}.
                </p>
            @endif
            </div>
        </div>
    </div>
    
    <div class="col-md-5">
        <div class="card card-bordered">
            <div class="card-inner">
                <h5>Item Pesanan</h5>
                @forelse($order?->items ?? [] as $item)
                <div class="d-flex justify-content-between border-bottom py-2">
                    <span>{{ $item->product_name }} × {{ $item->quantity }}</span><strong>Rp {{ number_format((float) $item->subtotal, 0, ',', '.') }}</strong>
                </div>
                @empty <p class="text-soft">
                    Item pesanan tidak tersedia.
                </p>
                @endforelse
                <div class="d-flex justify-content-between mt-3">
                    <strong>Total</strong><strong>Rp {{ number_format((float) ($order?->total ?? 0), 0, ',', '.') }}</strong>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

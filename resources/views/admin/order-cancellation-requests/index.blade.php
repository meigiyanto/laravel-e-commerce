@extends('layouts.admin')

@section('title', 'Permintaan Pembatalan')
@section('header', 'Permintaan Pembatalan')

@section('content')
<div class="nk-block-head nk-block-head-sm">
    <div class="nk-block-between">
        <div class="nk-block-head-content">
            <h3 class="nk-block-title page-title">Permintaan Pembatalan</h3>
            <p class="text-soft">Tinjau alasan customer dan putuskan setiap permintaan.</p>
        </div>
    </div>
</div>
<div class="card card-bordered"><div class="card-inner">
    @if($requests->isEmpty())
        <p class="text-center text-soft py-4">Belum ada permintaan pembatalan.</p>
    @else
        <div class="table-responsive"><table class="table table-middle">
            <thead><tr><th>Pesanan</th><th>Customer</th><th>Alasan</th><th>Status</th><th>Diajukan</th><th></th></tr></thead>
            <tbody>@foreach($requests as $item)
                <tr>
                    <td><a href="{{ route('admin.order-cancellation-requests.show', $item) }}">#{{ $item->order?->order_number ?? $item->order_id }}</a></td>
                    <td>{{ $item->user?->name ?? '-' }}</td>
                    <td>{{ \Illuminate\Support\Str::limit($item->reason, 90) }}</td>
                    <td><span class="badge {{ $item->status === 'pending' ? 'bg-warning text-dark' : ($item->status === 'approved' ? 'bg-success' : 'bg-danger') }}">{{ ucfirst($item->status) }}</span></td>
                    <td>{{ $item->created_at?->format('d M Y H:i') }}</td>
                    <td><a class="btn btn-sm btn-outline-primary" href="{{ route('admin.order-cancellation-requests.show', $item) }}">Tinjau</a></td>
                </tr>
            @endforeach</tbody>
        </table></div>
        {{ $requests->links() }}
    @endif
</div></div>
@endsection

@extends('layouts.storefront')

@section('title', 'Notifikasi')

@section('content')
<section class="container py-4">
    <h1 class="h3 mb-4">Notifikasi</h1>
    @forelse($notifications as $notification)
        <article class="card mb-3 {{ $notification->read_at ? '' : 'border-primary' }}"><div class="card-body">
            <p class="mb-2">{{ $notification->data['message'] ?? 'Ada pembaruan pada akun Anda.' }}</p>
            @if(!empty($notification->data['order_number']))<small class="text-muted">Pesanan #{{ $notification->data['order_number'] }}</small>@endif
            @if(!empty($notification->data['admin_note']))<p class="mt-2 mb-0">Catatan admin: {{ $notification->data['admin_note'] }}</p>@endif
            <div class="d-flex justify-content-between align-items-center mt-3"><small class="text-muted">{{ $notification->created_at->format('d M Y H:i') }}</small>
                @if(!$notification->read_at)<form method="POST" action="{{ route('notifications.read', $notification) }}">@csrf @method('PATCH')<button class="btn btn-sm btn-outline-primary">Baca</button></form>@elseif(!empty($notification->data['url']))<a class="btn btn-sm btn-outline-secondary" href="{{ $notification->data['url'] }}">Lihat</a>@endif
            </div>
        </div></article>
    @empty <p class="text-muted">Belum ada notifikasi.</p> @endforelse
    {{ $notifications->links() }}
</section>
@endsection

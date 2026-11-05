@extends('layouts.app')

@section('content')
<div class="container py-5">
    <div class="mx-auto" style="max-width: 560px">
        <h1 class="h4 mb-3">Undangan menjadi owner toko</h1>
        <p>Anda akan menerima undangan untuk mengelola toko ini.</p>
        <form method="POST" action="{{ route('tenant.invitations.accept', ['token' => $token]) }}">
            @csrf
            <button type="submit" class="btn btn-primary">Terima undangan</button>
        </form>
    </div>
</div>
@endsection
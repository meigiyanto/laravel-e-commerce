@extends('layouts.storefront')

@section('title', 'Terjadi Kesalahan')

@section('content')
    @include('errors.partials.storefront', [
        'status' => 500,
        'title' => 'Terjadi Kesalahan pada Sistem',
        'message' => 'Maaf, permintaan belum dapat diselesaikan. Silakan coba kembali beberapa saat lagi.',
    ])
@endsection

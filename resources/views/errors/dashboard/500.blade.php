@extends('layouts.admin')

@section('title', 'Terjadi Kesalahan')

@section('content')
    @include('errors.partials.dashboard', [
        'status' => 500,
        'title' => 'Terjadi Kesalahan pada Sistem',
        'message' => 'Maaf, permintaan belum dapat diselesaikan. Silakan coba kembali beberapa saat lagi.',
    ])
@endsection

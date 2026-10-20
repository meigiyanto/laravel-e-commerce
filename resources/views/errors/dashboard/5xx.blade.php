@extends('layouts.admin')

@section('title', 'Gangguan Layanan')

@section('content')
    @include('errors.partials.dashboard', [
        'status' => $exception->getStatusCode(),
        'title' => 'Layanan Sedang Mengalami Gangguan',
        'message' => 'Terjadi masalah pada layanan kami. Silakan coba kembali beberapa saat lagi.',
    ])
@endsection

@extends('layouts.dashboard')

@section('title', 'Layanan Sementara Tidak Tersedia')

@section('content')
    @include('errors.partials.dashboard', [
        'status' => 503,
        'title' => 'Kami Sedang Melakukan Pemeliharaan',
        'message' => 'Layanan sementara tidak tersedia. Silakan coba kembali nanti.',
    ])
@endsection

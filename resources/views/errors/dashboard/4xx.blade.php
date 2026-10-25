@extends('layouts.errors.dashboard')

@section('title', 'Permintaan Tidak Dapat Diproses')

@section('content')
    @include('errors.partials.dashboard', [
        'status' => $exception->getStatusCode(),
        'title' => 'Permintaan Tidak Dapat Diproses',
        'message' => 'Permintaan Anda tidak dapat diproses. Periksa kembali informasi yang dikirim atau coba lagi.',
    ])
@endsection

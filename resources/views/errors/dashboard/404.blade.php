@extends('layouts.dashboard')

@section('title', 'Halaman Tidak Ditemukan')

@section('content')
    @include('errors.partials.dashboard', [
        'status' => 404,
        'title' => 'Halaman Tidak Ditemukan',
        'message' => 'Halaman yang Anda cari mungkin telah dipindahkan atau tidak tersedia.',
    ])
@endsection

@extends('layouts.app')

@section('title', 'Dashboard')

@section('header', 'Dashboard')

@section('content')
<div class="nk-content ">
    <div class="container-fluid">
        <div class="nk-content-inner">
            <div class="nk-content-body">
                <h1>Dashboard</h1>
                <p>Selamat datang kembali, {{ Auth::user()->name }}</p>
            </div>
        </div>
     </div>
</div>
@endsection

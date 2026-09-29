@extends('layouts.app')

@section('title', 'Add Category')

@section('header', 'Add Category')

@section('content')
<div class="admin-container">

    <div class="page-header">
        <div>
            <h1>Tambah Kategori</h1>
            <p>Buat kategori produk baru.</p>
        </div>
    </div>

    <div class="card form-card">

        <form method="POST" action="{{ route('admin.categories.store') }}">
            @csrf

            <div class="form-group">
                <label for="name">Nama Kategori</label>

                <input
                    id="name"
                    type="text"
                    name="name"
                    value="{{ old('name') }}"
                    required
                >

                @error('name')
                    <small class="error-text">{{ $message }}</small>
                @enderror
            </div>

            <div class="form-group">
                <label for="slug">Slug</label>

                <input
                    id="slug"
                    type="text"
                    name="slug"
                    value="{{ old('slug') }}"
                    placeholder="Kosongkan untuk generate otomatis"
                >

                @error('slug')
                    <small class="error-text">{{ $message }}</small>
                @enderror
            </div>

            <div class="form-group">
                <label for="description">Deskripsi</label>

                <textarea
                    id="description"
                    name="description"
                    rows="5"
                >{{ old('description') }}</textarea>

                @error('description')
                    <small class="error-text">{{ $message }}</small>
                @enderror
            </div>

            <div class="form-actions">
                <a
                    href="{{ route('admin.categories.index') }}"
                    class="btn btn-secondary"
                >
                    Batal
                </a>

                <button type="submit" class="btn btn-primary">
                    Simpan
                </button>
            </div>

        </form>

    </div>
</div>
@endsection

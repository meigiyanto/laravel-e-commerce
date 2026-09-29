@extends('layouts.app')

@section('content')
<div class="admin-container">

    <div class="page-header">
        <div>
            <h1>Edit Kategori</h1>
            <p>Perbarui informasi kategori.</p>
        </div>
    </div>

    <div class="card form-card">

        <form method="POST" action="{{ route('admin.categories.update', $category) }}">
            @csrf
            @method('PUT')

            <div class="form-group">
                <label for="name">Nama Kategori</label>

                <input
                    id="name"
                    type="text"
                    name="name"
                    value="{{ old('name', $category->name) }}"
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
                    value="{{ old('slug', $category->slug) }}"
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
                >{{ old('description', $category->description) }}</textarea>

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
                    Update
                </button>
            </div>

        </form>

    </div>
</div>
@endsection

@extends('layouts.admin')

@section('title', 'Edit Category')
@section('header', 'Edit Category')

@section('content')
    <div class="nk-block-head nk-block-head-sm">
        <div class="nk-block-between">
            <div class="nk-block-head-content">
                <h3 class="nk-block-title page-title">Edit Category</h3>
                <div class="nk-block-des text-soft">
                    <p>Update Category Prodct</p>
                    @if(session('success'))
                        <div class="alert alert-success">
                            {{ session('success') }}
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="alert alert-error">
                            {{ session('error') }}
                        </div>
                    @endif
                </div>
            </div>

            <div class="nk-block-head-content">
                <a href="{{ route('admin.categories.index') }}" class="btn btn-outline-primary">
                    <em class="icon ni ni-arrow-left"></em>
                    <span>Go Back</span>
                </a>
            </div>

        </div>
    </div>

    <div class="card card-bordered">
        <div class="card-inner">
            <form method="POST" action="{{ route('admin.categories.update', $category) }}">
                @csrf
                @method('PUT')
    
                <div class="form-group">
                    <label for="name">Nama Kategori</label>
    
                    <input
                        id="name"
                        class="form-control"
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
                        class="form-control"
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
                        Cancel
                    </a>
    
                    <button type="submit" class="btn btn-primary">
                        Update
                    </button>
                </div>
    
            </form>
        </div>
    </div>
@endsection

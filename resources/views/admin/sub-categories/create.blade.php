@extends('layouts.app')

@section('title', 'Add Sub-Category')
@section('header', 'Add Sub-Category')

@section('content')
<div class="nk-content-body">
    <div class="nk-block-head nk-block-head-sm">
        <div class="nk-block-between">
            <div class="nk-block-head-content">
                <h3 class="nk-block-title page-title">Add Sub Categories</h3>
                <div class="nk-block-des text-soft">
                    <p>Create New Sub Category Product</p>
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
                <a href="{{ route('admin.sub-categories.index') }}" class="btn btn-outline-primary">
                    <em class="icon ni ni-arrow-left"></em>
                    <span>Go Back</span>
                </a>
            </div>

        </div>
    </div>

    <div class="card card-bordered">
        <div class="card-inner">
            <form
                method="POST"
                action="{{ route('admin.sub-categories.store') }}"
            >
                @csrf
                <div class="form-group">
                    <label for="category_id">Category</label>
                    <select
                        class="form-control"
                        id="category_id"
                        name="category_id"
                        required
                    >
                        <option value="">-- Select Category --</option>
                        @foreach($categories as $category)
                            <option
                                value="{{ $category->id }}"
                                @selected(old('category_id') == $category->id)
                            >
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('category_id')
                        <span class="error-text">{{ $message }}</span>
                    @enderror
                </div>
                <div class="form-group">
                    <label for="name">Name</label>
                    <input
                        type="text"
                        class="form-control"
                        id="name"
                        name="name"
                        value="{{ old('name') }}"
                        required
                    >
                    @error('name')
                        <span class="error-text">{{ $message }}</span>
                    @enderror
                </div>
                <div class="form-group">
                    <label for="slug">Slug</label>
                    <input
                        type="text"
                        class="form-control"
                        id="slug"
                        name="slug"
                        value="{{ old('slug') }}"
                        placeholder="Akan dibuat otomatis jika kosong"
                    >
                    @error('slug')
                        <span class="error-text">{{ $message }}</span>
                    @enderror
                </div>
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea
                        class="form-control"
                        id="description"
                        name="description"
                        rows="5"
                    >{{ old('description') }}</textarea>
    
                    @error('description')
                        <span class="error-text">{{ $message }}</span>
                    @enderror
                </div>
                <div class="form-actions">
                    <a
                        href="{{ route('admin.sub-categories.index') }}"
                        class="btn btn-secondary"
                    >
                        Cancel
                    </a>
    
                    <button
                        type="submit"
                        class="btn btn-primary"
                    >
                        Create
                    </button>
                </div>
            </form>

        </div>
    </div>

</div>
@endsection

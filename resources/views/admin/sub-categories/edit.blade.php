@extends('layouts.app')

@section('title', 'Edit Sub-Category')
@section('header', 'Edit Sub-Category')

@section('content')
<div class="nk-content-body">
    <div class="nk-block-head nk-block-head-sm">
        <div class="nk-block-between">
            <div class="nk-block-head-content">
                <h3 class="nk-block-title page-title">Edit Sub Categories</h3>
                <div class="nk-block-des text-soft">
                    <p>Update Sub Category Product</p>
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
                <a href="{{ route('admin.sub-categories.create') }}" class="btn btn-outline-primary">
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
                action="{{ route('admin.sub-categories.update', $subCategory) }}"
            >
                @csrf
                @method('PUT')
                <div class="form-group">
                    <label for="category_id">Category</label>
                    <select
                        id="category_id"
                        name="category_id"
                        required
                    >
                        <option value="">-- Select Category --</option>
                        @foreach($categories as $category)
                            <option
                                value="{{ $category->id }}"
                                @selected(old('category_id', $subCategory->category_id) == $category->id)
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
                        value="{{ old('name', $subCategory->name) }}"
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
                        value="{{ old('slug', $subCategory->slug) }}"
                    >
                    @error('slug')
                        <span class="error-text">{{ $message }}</span>
                    @enderror
                </div>
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea
                        id="description"
                        name="description"
                        rows="5"
                    >{{ old('description', $subCategory->description) }}</textarea>
    
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
                        Update
                    </button>
    
                </div>
            </form>

        </div>
    </div>

</div>    
@endsection

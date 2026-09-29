@extends('layouts.app')

@section('title', 'Edit Sub-Category')

@section('header', 'Edit Sub-Category')

@section('content')

<div class="page-container">

    <div class="page-title">
        <h1>Edit Sub-Category</h1>
        <p>Perbarui informasi sub-kategori.</p>
    </div>

    <div class="content-card form-card">

        <form
            method="POST"
            action="{{ route('admin.sub-categories.update', $subCategory) }}"
        >

            @csrf
            @method('PUT')

            <div class="form-group">

                <label for="category_id">
                    Category
                </label>

                <select
                    id="category_id"
                    name="category_id"
                    required
                >

                    <option value="">
                        -- Select Category --
                    </option>

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
                    <span class="error-text">
                        {{ $message }}
                    </span>
                @enderror

            </div>


            <div class="form-group">

                <label for="name">
                    Name
                </label>

                <input
                    type="text"
                    id="name"
                    name="name"
                    value="{{ old('name', $subCategory->name) }}"
                    required
                >

                @error('name')
                    <span class="error-text">
                        {{ $message }}
                    </span>
                @enderror

            </div>


            <div class="form-group">

                <label for="slug">
                    Slug
                </label>

                <input
                    type="text"
                    id="slug"
                    name="slug"
                    value="{{ old('slug', $subCategory->slug) }}"
                >

                @error('slug')
                    <span class="error-text">
                        {{ $message }}
                    </span>
                @enderror

            </div>


            <div class="form-group">

                <label for="description">
                    Description
                </label>

                <textarea
                    id="description"
                    name="description"
                    rows="5"
                >{{ old('description', $subCategory->description) }}</textarea>

                @error('description')
                    <span class="error-text">
                        {{ $message }}
                    </span>
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

@endsection

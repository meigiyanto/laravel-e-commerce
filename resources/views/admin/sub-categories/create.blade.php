@extends('layouts.app')

@section('title', 'Add Sub-Category')

@section('header', 'Add Sub-Category')

@section('content')

<div class="page-container">

    <div class="page-title">
        <h1>Add Sub-Category</h1>
        <p>Tambahkan sub-kategori baru.</p>
    </div>

    <div class="content-card form-card">

        <form
            method="POST"
            action="{{ route('admin.sub-categories.store') }}"
        >

            @csrf

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
                            @selected(old('category_id') == $category->id)
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
                    value="{{ old('name') }}"
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
                    value="{{ old('slug') }}"
                    placeholder="Akan dibuat otomatis jika kosong"
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
                >{{ old('description') }}</textarea>

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
                    Save
                </button>

            </div>

        </form>

    </div>

</div>

@endsection

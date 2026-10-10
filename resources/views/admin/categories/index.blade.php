@extends('layouts.admin')

@section('title', 'Categories')
@section('header', 'Categories')

@section('content')
    <div class="nk-block-head nk-block-head-sm">
        <div class="nk-block-between">
            <div class="nk-block-head-content">
                <h3 class="nk-block-title page-title">Categories</h3>
                <div class="nk-block-des text-soft">
                    <p>Lists of Category Product</p>
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
                <a href="{{ route('admin.categories.create') }}" class="btn btn-outline-primary">
                    <em class="icon ni ni-plus"></em>
                    <span>Add Category</span>
                </a>
            </div>

        </div>
    </div>

    <div class="card card-bordered">
        <div class="card-inner">

            <div class="table-responsive">
                <table class="table table-middle datatable-init">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Slug</th>
                            <th>Subcategories</th>
                            <th class="no-sort text-end">Action</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($categories as $category)
                            <tr>
                                <td>{{ $category->id }}</td>
                                <td>{{ $category->name }}</td>
                                <td>{{ $category->slug }}</td>
                                <td>{{ $category->sub_categories_count }}</td>
                                <td>
                                    <div class="btn-group">
                                        <a
                                            href="{{ route('admin.categories.edit', $category) }}"
                                            class="btn btn-secondary"
                                        >
                                            Edit
                                        </a>

                                        <form
                                            method="POST"
                                            action="{{ route('admin.categories.destroy', $category) }}"
                                            onsubmit="return confirm('Hapus kategori ini?')"
                                        >
                                            @csrf
                                            @method('DELETE')

                                            <button
                                                type="submit"
                                                class="btn btn-danger"
                                            >
                                                Delete
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                @if ($categories->isEmpty())
                    <div class="text-center text-soft py-4">
                        No category.
                    </div>
                @endif
            </div>

        </div>
    </div>
@endsection

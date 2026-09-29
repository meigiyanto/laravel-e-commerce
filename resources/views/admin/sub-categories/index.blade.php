@extends('layouts.app')

@section('title', 'Sub-Categories')

@section('header', 'Sub-Categories')

@section('content')

<div class="page-container">

    <div class="page-title">

        <div style="display: flex; justify-content: space-between; align-items: center; gap: 15px; flex-wrap: wrap;">

            <div>
                <h1>Sub-Categories</h1>
                <p>Kelola sub-kategori produk.</p>
            </div>

            <a
                href="{{ route('admin.sub-categories.create') }}"
                class="btn btn-primary"
            >
                + Add Sub-Category
            </a>

        </div>

    </div>

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

    <div class="content-card">

        <form
            method="GET"
            action="{{ route('admin.sub-categories.index') }}"
            style="margin-bottom: 20px;"
        >

            <div style="display: flex; gap: 10px;">

                <input
                    type="text"
                    name="search"
                    value="{{ $search }}"
                    placeholder="Search sub-category..."
                    class="form-control"
                >

                <button
                    type="submit"
                    class="btn btn-primary"
                >
                    Search
                </button>

            </div>

        </form>

        <div class="table-wrapper">

            <table class="data-table">

                <thead>
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Slug</th>
                        <th>Category</th>
                        <th>Products</th>
                        <th>Actions</th>
                    </tr>
                </thead>

                <tbody>

                    @forelse($subCategories as $subCategory)

                        <tr>

                            <td>
                                {{ $subCategories->firstItem() + $loop->index }}
                            </td>

                            <td>
                                <strong>
                                    {{ $subCategory->name }}
                                </strong>
                            </td>

                            <td>
                                {{ $subCategory->slug }}
                            </td>

                            <td>
                                {{ $subCategory->category->name }}
                            </td>

                            <td>
                                {{ $subCategory->products()->count() }}
                            </td>

                            <td>

                                <div class="action-group">

                                    <a
                                        href="{{ route('admin.sub-categories.edit', $subCategory) }}"
                                        class="btn btn-secondary"
                                    >
                                        Edit
                                    </a>

                                    <form
                                        method="POST"
                                        action="{{ route('admin.sub-categories.destroy', $subCategory) }}"
                                        onsubmit="return confirm('Hapus sub-category ini?')"
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

                    @empty

                        <tr>
                            <td colspan="6">
                                Belum ada sub-category.
                            </td>
                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

        <div style="margin-top: 20px;">
            {{ $subCategories->links() }}
        </div>

    </div>

</div>

@endsection

@extends('layouts.app')

@section('title', 'Sub-Categories')
@section('header', 'Sub-Categories')

@section('content')
<div class="nk-content-body">
    <div class="nk-block-head nk-block-head-sm">
        <div class="nk-block-between">
            <div class="nk-block-head-content">
                <h3 class="nk-block-title page-title">Sub Categories</h3>
                <div class="nk-block-des text-soft">
                    <p>Lists of Sub Category Product</p>
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
                    <em class="icon ni ni-plus"></em>
                    <span>Add Sub Category</span>
                </a>
            </div>

        </div>
    </div>

    <div class="card card-bordered">
        <div class="card-inner">
            <form
                method="GET"
                action="{{ route('admin.sub-categories.index') }}"
                style="margin-bottom: 20px;"
            >
                <div class="form-control-wrap">
                    <div class="input-group">
                        <input type="text" class="form-control" name="search" value="{{ $search }}" placeholder="Search sub-category">
                        <div class="input-group-append">
                            <button type="submit" class="btn btn-outline-primary btn-dim">Search</button>
                        </div>
                    </div>
                </div>

            </form>

            <table class="nowrap table table-bordered table-striped">
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
                                <div class="btn-group">
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
    </div>

</div>

@endsection

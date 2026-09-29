@extends('layouts.app')

@section('title', 'Products')

@section('header', 'Products')

@section('content')
<div class="page-container">
    <div class="page-title">

        <div style="display: flex; justify-content: space-between; align-items: center; gap: 15px; flex-wrap: wrap;">

            <div>
                <h1>Products</h1>
                <p>Kelola produk toko.</p>
            </div>

            <a href="{{ route('admin.products.create') }}" class="btn btn-primary">
                + Add Product
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
        <div class="table-wrapper">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Product</th>
                        <th>Category</th>
                        <th>Sub-Category</th>
                        <th>Price</th>
                        <th>Stock</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($products as $product)
                        <tr>
                            <td>
                                {{ $products->firstItem() + $loop->index }}
                            </td>
                            <td>
                                <strong>
                                    {{ $product->name }}
                                </strong>
                            </td>
                            <td>
                                {{ $product->subCategory->category->name }}
                            </td>
                            <td>
                                {{ $product->subCategory->name }}
                            </td>
                            <td>
                                Rp {{ number_format($product->price, 0, ',', '.') }}
                            </td>
                            <td>
                                {{ $product->stock }}
                            </td>
                            <td>
                                <div class="action-group">

                                    <a href="{{ route('admin.products.edit', $product) }}" class="btn btn-secondary">Edit</a>

                                    <form method="POST" action="{{ route('admin.products.destroy', $product) }}" onsubmit="return confirm('Hapus product ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger">
                                            Delete
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7">
                                Belum ada product.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- <div style="margin-top: 20px;"> -->
        <div class="pagination-wrapper">
            {{ $products->links() }}
        </div>
    </div>
</div>

@endsection

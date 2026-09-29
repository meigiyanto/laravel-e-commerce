@extends('layouts.app')

@section('title', 'Products')
@section('header', 'Products')

@section('content')
<div class="nk-content-body">
    <div class="nk-block-head nk-block-head-sm">
        <div class="nk-block-between">
            <div class="nk-block-head-content">
                <h3 class="nk-block-title page-title">Products</h3>
                <div class="nk-block-des text-soft">
                    <p>Kelola produk toko.</p>
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
                <a href="{{ route('admin.products.create') }}" class="btn btn-outline-primary">
                    <em class="icon ni ni-plus"></em>
                    <span>Add Product</span>
                </a>
            </div>

        </div>
    </div>

    <div class="card card-bordered">
        <div class="card-inner">

            <table class="nowrap table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Product</th>
                        <th>Category</th>
                        <th>Sub Category</th>
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
                                <div class="btn-group">

                                    <a href="{{ route('admin.products.edit', $product) }}" class="btn btn-sm btn-secondary"><em class="ni ni-edit"></em></a>

                                    <form method="POST" action="{{ route('admin.products.destroy', $product) }}" onsubmit="return confirm('Hapus product ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger"><em class="ni ni-trash"></em></button>
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
    </div>

</div>

@endsection

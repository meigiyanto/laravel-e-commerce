@extends('layouts.admin')

@section('title', 'Products')
@section('header', 'Products')

@section('content')
    <div class="nk-block-head nk-block-head-sm">
        <div class="nk-block-between">
            <div class="nk-block-head-content">
                <h3 class="nk-block-title page-title">Products</h3>
                <div class="nk-block-des text-soft">
                    <p>Lists of all product</p>
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

        @if($products->isEmpty())
            <div class="text-center text-soft py-4">
                 No product yet.
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-bordered table-striped datatable-init text-nowrap">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Product</th>
                            <th>Category</th>
                            <th>Sub Category</th>
                            <th>Price</th>
                            <th>Stock</th>
                            <th class="no-sort">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($products as $product)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td><strong>{{ $product->name }}</strong></td>
                                <td>{{ $product->category?->name ?? '-' }}</td>
                                <td>{{ $product->subCategory?->name ?? '-' }}</td>    
                                <td>Rp {{ number_format($product->price, 0, ',', '.') }}</td>
                                <td>{{ $product->stock }}</td>
                                <td>
                                    <div class="btn-group">
                                        <a href="{{ route('admin.products.edit', $product) }}" class="btn btn-sm btn-info" title="Show">
                                            <em class="ni ni-eye"></em>
                                        </a>
                                        <a href="{{ route('admin.products.edit', $product) }}" class="btn btn-sm btn-secondary" title="Edit">
                                            <em class="ni ni-edit"></em>
                                        </a>
                                        <form method="POST" class="d-inline" action="{{ route('admin.products.destroy', $product) }}" onsubmit="return confirm('Are you sure want to delete this product?')">
                                            @csrf
                                            @method('DELETE')
                                        
                                            <a href="#" class="btn btn-sm btn-danger pt-1" title="Delete" onclick="event.preventDefault(); this.closest('form').requestSubmit();">
                                                <em class="ni ni-trash"></em>
                                            </a>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        </div>
    </div>
@endsection

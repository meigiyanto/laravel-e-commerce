@extends('layouts.admin')

@section('title', 'Products')
@section('header', 'Products')

@section('content')
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
            <div class="dropdown">
              <button class="btn btn-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                Dropdown button
              </button>
              <ul class="dropdown-menu">
                <li><a class="dropdown-item" href="#">Action</a></li>
                <li><a class="dropdown-item" href="#">Another action</a></li>
                <li><a class="dropdown-item" href="#">Something else here</a></li>
              </ul>
            </div>
        </div>
    </div>

    <div class="card card-bordered">
        <div class="card-inner">

            <div>
                <table class="table table-bordered table-striped datatable-init">
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
                                <td>
                                    {{ $loop->iteration }}
                                </td>

                                <td>
                                    <strong>
                                        {{ $product->name }}
                                    </strong>
                                </td>

                                <td>
                                    {{ $product->category?->name ?? '-' }}
                                </td>

                                <td>
                                    {{ $product->subCategory?->name ?? '-' }}
                                </td>

                                <td>
                                    Rp {{ number_format($product->price, 0, ',', '.') }}
                                </td>

                                <td>
                                    {{ $product->stock }}
                                </td>

                                <td>
                                    <div class="btn-group">
                                        <!-- <a
                                            href="{{ route('admin.products.edit', $product) }}"
                                            class="btn btn-sm btn-secondary"
                                            title="Edit"
                                        >
                                            <em class="ni ni-edit"></em>
                                        </a> -->

                                        <button
                                            type="submit"
                                            class="btn btn-sm btn-secondary"
                                            title="Delete"
                                            onclick="window.location.href='{{ route('admin.products.edit', $product) }}"
                                        >
                                            <em class="ni ni-edit"></em>
                                        </button>

                                        <form
                                            method="POST"
                                            action="{{ route('admin.products.destroy', $product) }}"
                                            onsubmit="return confirm('Hapus product ini?')"
                                        >
                                            @csrf
                                            @method('DELETE')

                                            <button
                                                type="submit"
                                                class="btn btn-sm btn-danger pt-1"
                                                title="Delete"
                                            >
                                                <em class="ni ni-trash"></em>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($products->isEmpty())
                <div class="text-center text-soft py-4">
                    Belum ada product.
                </div>
            @endif

        </div>
    </div>
@endsection

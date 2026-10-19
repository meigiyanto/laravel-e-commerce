@extends('layouts.admin')

@section('title', 'Products')
@section('header', 'Products')

@section('content')
    <div class="nk-block-head nk-block-head-sm">
        <div class="nk-block-between">
            <div class="nk-block-head-content">
                <h3 class="nk-block-title page-title">Produk</h3>
                <div class="nk-block-des text-soft">
                    <p>Daftar seluruh produk</p>
                </div>
            </div>
            <div class="nk-block-head-content">
                <a href="{{ route('admin.products.create') }}" class="btn btn-outline-primary">
                    <em class="icon ni ni-plus"></em>
                    <span>Tambah produk</span>
                </a>
            </div>
        </div>
    </div>

    <div class="card card-bordered">
        <div class="card-inner">

        @if($products->isEmpty())
            <div class="text-center text-soft py-4">
                 Belum ada produk.
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-bordered table-striped datatable-init text-nowrap">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Produk</th>
                            <th>Kategori</th>
                            <th>Sub Kategori</th>
                            <th>Harga</th>
                            <th>Stok</th>
                            <th class="no-sort">Aksi</th>
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
                                        <a href="{{ route('admin.products.edit', $product) }}" class="btn btn-sm btn-secondary" title="Edit produk" aria-label="Edit {{ $product->name }}">
                                            <em class="icon ni ni-edit" aria-hidden="true"></em>
                                        </a>
                                        <form method="POST" class="d-inline" action="{{ route('admin.products.destroy', $product) }}" onsubmit="return confirm('Apakah Anda yakin ingin menghapus produk ini?')" >
                                            @csrf
                                            @method('DELETE')
                                        
                                            <button type="submit" class="btn btn-sm btn-danger" title="Hapus produk" aria-label="Hapus {{ $product->name }}" >
                                                <em class="icon ni ni-trash" aria-hidden="true"></em>
                                            </button>
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

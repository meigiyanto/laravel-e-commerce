@extends('layouts.admin')

@section('title', 'Inventory')
@section('header', 'Inventory')

@section('content')
    {{-- Header --}}
    <div class="nk-block-head nk-block-head-sm">
        <div class="nk-block-between">
            <div class="nk-block-head-content">
                <h3 class="nk-block-title page-title">Inventory</h3>
                <div class="nk-block-des text-soft">
                    <p>Manage stock product and monitor product with low stock</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Flash Message --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            <em class="icon ni ni-check-circle"></em>
            <span> {{ session('success') }}</span>
            <button type="button" class="close" data-bs-dismiss="alert">
                <span>&times;</span>
            </button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">
            <em class="icon ni ni-alert-circle"></em>
            <span>{{ session('error') }}</span>
            <button type="button" class="close" data-bs-dismiss="alert">
                <span>&times;</span>
            </button>
        </div>
    @endif

    {{-- Validation Errors --}}
    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Stock Summary --}}
    <div class="row g-gs mb-4">
        {{-- Out of Stock --}}
        <div class="col-md-4">
            <div class="card card-bordered">
                <div class="card-inner">
                    <div class="card-title-group">
                        <div class="card-title">
                            <h6 class="title">Out of Stock</h6>
                        </div>
                        <div class="card-tools">
                            <em class="icon ni ni-alert-circle text-danger"></em>
                        </div>
                    </div>

                    <div class="card-amount mt-2">
                        <p><span class="amount">{{ $stockCounts['out_of_stock'] }}</span> product(s)</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Low Stock --}}
        <div class="col-md-4">
            <div class="card card-bordered">
                <div class="card-inner">
                    <div class="card-title-group">
                        <div class="card-title">
                            <h6 class="title">Low Stock</h6>
                        </div>

                        <div class="card-tools">
                            <em class="icon ni ni-alert text-warning"></em>
                        </div>
                    </div>

                    <div class="card-amount mt-2">
                        <p><span class="amount">{{ $stockCounts['low_stock'] }}</span> product(s)</p>
                    </div>

                    <div class="card-note">Stock 1–{{ $lowStockThreshold }}</div>
                </div>
            </div>
        </div>

        {{-- In Stock --}}
        <div class="col-md-4">
            <div class="card card-bordered">
                <div class="card-inner">
                    <div class="card-title-group">
                        <div class="card-title">
                            <h6 class="title">In Stock</h6>
                        </div>
                        <div class="card-tools">
                            <em class="icon ni ni-check-circle text-success"></em>
                        </div>
                    </div>
                    <div class="card-amount mt-2">
                        <p><span class="amount">{{ $stockCounts['in_stock'] }}</span> product(s)</p>
                    </div>
                    <div class="card-note">Stock &gt; {{ $lowStockThreshold }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Inventory Table --}}
    <div class="card car-bordered">
        <div class="card-inner">
            {{-- Search --}}
            <form method="GET" action="{{ route('admin.inventory.index') }}" class="mb-4">
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label">Stock Status</label>

                            <div class="form-control-wrap">
                                <select
                                    name="stock_status"
                                    class="form-select"
                                >
                                    <option value="">All Status</option>

                                    <option
                                        value="out_of_stock"
                                        @selected($stockStatus === 'out_of_stock')
                                    >
                                        Out of Stock
                                    </option>

                                    <option
                                        value="low_stock"
                                        @selected($stockStatus === 'low_stock')
                                    >
                                        Low Stock
                                    </option>

                                    <option
                                        value="in_stock"
                                        @selected($stockStatus === 'in_stock')
                                    >
                                        In Stock
                                    </option>

                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4 d-flex align-items-end">
                        <button
                            type="submit"
                            class="btn btn-primary w-50 mx-1"
                        >
                            <em class="icon ni ni-search"></em>
                            Filter
                        </button>
                    @if($search || $stockStatus)
                        <button
                            type="submit"
                            class="btn btn-outline-primary w-50 mx-1"
                            onclick="window.location.href='{{ route('admin.inventory.index') }}'"
                        >
                            Reset
                        </button>
                    @endif
                    </div>
                </div>
            </form>

            {{-- Table --}}
            <div>
                <table class="table table-hover datatable-init">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>Stock</th>
                            <th>Status</th>
                            <th class="text-end no-sort">Action</th>
                        </tr>
                    </thead>

                    <tbody>
                    @foreach($products as $product)
                        <tr>
                            {{-- Product --}}
                            <td>
                                <div class="user-card">
                                    <div class="user-avatar bg-primary">
                                        <span>{{ strtoupper(substr($product->name, 0, 1)) }}</span>
                                    </div>
                                    <div class="user-info">
                                        <span class="tb-lead">{{ $product->name }}</span>
                                        <span class="text-soft">ID #{{ $product->id }}</span>
                                    </div>
                                </div>
                            </td>
                            {{-- Category --}}
                            <td>{{ $product->category?->name ?? '-' }}</td>
                            {{-- Price --}}
                            <td>Rp {{ number_format($product->price, 0, ',', '.') }}</td>
                            {{-- Stock --}}
                            <td><span class="fw-bold">{{ $product->stock }}</span></td>
                            {{-- Status --}}
                            <td>
                                @if($product->stock <= 0)
                                    <span class="badge bg-danger p-1">Out of Stock</span>
                                @elseif($product->stock <= $lowStockThreshold)
                                    <span class="badge bg-warning p-1">Low Stock</span>
                                @else
                                    <span class="badge bg-success p-1">In Stock</span>
                                @endif
                            </td>

                            {{-- Action --}}
                            <td class="text-end">
                                <div class="d-flex justify-content-end gap-2">
                                    {{-- Set Stock --}}
                                    <button
                                        type="button"
                                        data-bs-toggle="modal"
                                        data-bs-target="#stockModal{{ $product->id }}"
                                        class="btn btn-md p-2 btn-outline-primary"
                                    >
                                        <em class="icon ni ni-edit"></em>
                                        Edit
                                    </button>

                                    {{-- Add Stock --}}
                                    <button
                                        type="button"
                                        data-bs-toggle="modal"
                                        data-bs-target="#adjustModal{{ $product->id }}"
                                        class="btn btn-md p-2 btn-outline-success"
                                    >
                                        <em class="icon ni ni-plus"></em>
                                        Adjust
                                    </button>
                                </div>
                            </td>
                            
                        </tr>
                    @endforeach
                    </tbody>
                </table>

                @foreach($products as $product)
                    {{-- Set Stock Modal --}}
                    <div
                        class="modal fade"
                        id="stockModal{{ $product->id }}"
                        tabindex="-1"
                    >
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Update Stock</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>


                                <form
                                    method="POST"
                                    action="{{ route('admin.inventory.update-stock', $product) }}"
                                >
                                    @csrf
                                    @method('PATCH')
                                    <div class="modal-body">
                                        <div class="mb-3">
                                            <strong>{{ $product->name }}</strong>
                                        </div>


                                        <div class="form-group">
                                            <label class="form-label">Stock</label>

                                            <input
                                                type="number"
                                                name="stock"
                                                value="{{ $product->stock }}"
                                                min="0"
                                                class="form-control"
                                                required
                                            >

                                            <div class="form-note">Stok tidak boleh kurang dari 0.</div>
                                        </div>
                                    </div>

                                    <div class="modal-footer">
                                        <button
                                            type="button"
                                            class="btn btn-light"
                                            data-bs-dismiss="modal"
                                        >
                                            Batal
                                        </button>

                                        <button
                                            type="submit"
                                            class="btn btn-primary"
                                        >
                                            Simpan
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    {{-- Adjust Stock Modal --}}
                    <div
                        class="modal fade"
                        id="adjustModal{{ $product->id }}"
                        tabindex="-1"
                    >
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Adjust Stock</h5>
                                        <button
                                            type="button"
                                            class="btn-close"
                                            data-bs-dismiss="modal"
                                        ></button>
                                    </div>
    
                                    <form
                                        method="POST"
                                        action="{{-- route('admin.inventory.adjust-stock', $product) --}}"
                                    >
    
                                        @csrf
                                        <div class="modal-body">
                                            <div class="mb-3">
                                                <strong>{{ $product->name }}</strong>
                                                <div class="text-soft">Stok saat ini: {{ $product->stock }}</div>
                                            </div>
                                            <div class="form-group mb-3">
                                                <label class="form-label">Change Type</label>
    
                                                <select
                                                    name="type"
                                                    class="form-select"
                                                    required
                                                >
                                                    <option value="add">Increase Stock</option>
                                                    <option value="subtract">Decrease Stock</option>
                                                </select>
                                            </div>
    
                                            <div class="form-group">
                                                <label class="form-label">Jumlah</label>
                                                <input
                                                    type="number"
                                                    name="quantity"
                                                    min="1"
                                                    class="form-control"
                                                    placeholder="Contoh: 10"
                                                    required
                                                >
                                            </div>
                                        </div>
    
                                        <div class="modal-footer">
                                            <button
                                                type="button"
                                                class="btn btn-light"
                                                data-bs-dismiss="modal"
                                            >
                                                Batal
                                            </button>
    
                                            <button
                                                type="submit"
                                                class="btn btn-primary"
                                            >
                                                Simpan
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach

                @if($products->isEmpty())
                    <div class="text-center text-soft py-5">
                        Tidak ada produk ditemukan.
                    </div>
                @endif

            </div>

        </div>
    </div>
@endsection

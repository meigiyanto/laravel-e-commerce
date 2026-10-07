@extends('layouts.storefront')

@section('title', config('app.name') . ' - Compare Product')

@section('content')
{{-- =========================================================
    BREADCRUMB
========================================================= --}}
<div class="store-breadcrumb">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item">
                    <a href="{{ route('storefront.home') }}">
                        <i class="bi bi-house me-1"></i>
                        Home
                    </a>
                </li>
                <li class="breadcrumb-item active" aria-current="page">
                    Compare
                </li>
            </ol>
        </nav>
    </div>
</div>

<section class="container py-4 py-lg-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="store-section-title">Compare Product<h1>
            <p class="store-section-subtitle">Compare product until 4 product</p>
        </div>

        @if ($products->isNotEmpty())
            <form action="{{ route('compare.clear') }}" method="POST">
                @csrf
                @method('DELETE')
                <button class="btn btn-outline-danger btn-sm">Empty</button>
            </form>
        @endif
    </div>

    @if ($products->isEmpty())
        <div class="alert alert-light border">
            <h3>Unavailable product to compare.</h3>
            <p>Oops! There are no comparable products</p>
            <a href="{{ url('shop') }}" class="btn btn-primary me-2"><i class="bi bi-cart"></i> Start Shopping</a>
        </div>
    @else
        <div>
            <table class="table table-bordered table-striped align-middle ">
                <tbody>
                    <tr>
                        <th>Product</th>
                        @foreach ($products as $product)
                            <td class="text-center" style="min-width:180px;">
                                @if ($product->image)
                                    <img src="{{ $product->image }}" alt="{{ $product->name }}" class="img-fluid rounded mb-2" style="height:140px;object-fit:cover">
                                @endif
                                <div class="fw-bold">{{ $product->name }}</div>

                                <form action="{{ route('compare.destroy', $product) }}" method="POST" class="mt-2">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger">
                                        Remove
                                    </button>
                                </form>
                            </td>
                        @endforeach
                    </tr>

                    <tr>
                        <th>Price</th>
                        @foreach ($products as $product)
                            <td class="text-center fw-bold">
                                Rp {{ number_format($product->price, 0, ',', '.') }}
                            </td>
                        @endforeach
                    </tr>

                    <tr>
                        <th>Category</th>
                        @foreach ($products as $product)
                            <td class="text-center">
                                {{ $product->category?->name ?? '-' }}
                            </td>
                        @endforeach
                    </tr>

                    <tr>
                        <th>Sub Category</th>
                        @foreach ($products as $product)
                            <td class="text-center">
                                {{ $product->subcategory?->name ?? '-' }}
                            </td>
                        @endforeach
                    </tr>

                    <tr>
                        <th>Stock</th>
                        @foreach ($products as $product)
                            <td class="text-center">
                                {{ $product->stock > 0 ? 'Available' : 'Unavailable' }}
                            </td>
                        @endforeach
                    </tr>

                    <tr>
                        <th>Description</th>
                        @foreach ($products as $product)
                            <td>
                                {{ $product->description ? \Illuminate\Support\Str::limit($product->description, 120) : '-' }}
                            </td>
                        @endforeach
                    </tr>
                </tbody>
            </table>
        </div>
    @endif
</section>
@endsection

@extends('layouts.storefront')

@section('title', config('app.name') . ' - Compare Products')

@section('content')
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
                <li class="breadcrumb-item">Account</li>
                <li class="breadcrumb-item active" aria-current="page">
                    Compare Products
                </li>
            </ol>
        </nav>
    </div>
</div>

@include('components.account.page-header', [
    'title' => 'Compare Products',
    'description' => 'Compare prices, categories, stock, and product details in one place.',
    'eyebrow' => 'Make the right choice',
])

<section class="pb-5">
    <div class="container">
        <div class="row g-4">
            {{-- Sidebar --}}
            <div class="col-lg-4">
                @include('components.account.sidebar')
            </div>

            {{-- Main Content --}}
            <div class="col-lg-8">
                @if ($products->isNotEmpty())
                    <form action="{{ route('account.compare.clear') }}" method="POST">
                        @csrf
                        @method('DELETE')
    
                        <button type="submit" class="store-compare-clear">
                            <i class="bi bi-trash3 me-1"></i>
                            Clear comparison
                        </button>
                    </form>
                @endif
                
                @if ($products->isEmpty())
                    <div class="store-empty-state">
                        <div class="store-empty-icon">
                            <i class="bi bi-bar-chart-line"></i>
                        </div>
        
                        <h2 class="store-empty-title">No products to compare yet</h2>
                        <p class="store-empty-text">
                            Add products to your comparison list to review their prices and features side by side.
                        </p>
        
                        <a href="{{ route('storefront.shop') }}" class="store-btn-primary">
                            <i class="bi bi-shop me-1"></i>
                            Explore products
                        </a>
                    </div>
                @else
                    <div class="store-compare">
                        <div class="store-compare-controls" aria-label="Navigasi tabel perbandingan">
                            <span class="store-compare-hint">
                                <i class="bi bi-arrow-left-right me-1" aria-hidden="true"></i>
                                Swipe horizontally to compare
                            </span>
                
                            <div class="store-compare-arrows">
                                <button
                                    type="button"
                                    class="btn btn-outline-secondary btn-sm store-compare-arrow"
                                    data-compare-direction="left"
                                    aria-label="Geser tabel ke kiri"
                                    disabled
                                >
                                    <i class="bi bi-chevron-left" aria-hidden="true"></i>
                                </button>
                
                                <button
                                    type="button"
                                    class="btn btn-outline-secondary btn-sm store-compare-arrow"
                                    data-compare-direction="right"
                                    aria-label="Geser tabel ke kanan"
                                >
                                    <i class="bi bi-chevron-right" aria-hidden="true"></i>
                                </button>
                            </div>
                        </div>
                
                        <div class="store-compare-scroll" tabindex="0" role="region" aria-label="Tabel perbandingan produk" data-compare-scroll>
                            <table class="table table-bordered table-striped align-middle store-compare-table">
                                <tbody>
                                    <tr>
                                        <th scope="row">Product</th>
                                        @foreach ($products as $product)
                                            <td class="text-center store-compare-product">
                                                <img src="{{ $product->image_url ?: asset('placeholder.png')}}" alt="{{ $product->name }}" class="img-fluid rounded mb-2 store-compare-image" loading="lazy" onerror="this.onerror=null;this.src='{{ asset('storage/products/placeholder.png') }}';">
                                                <div class="fw-bold">{{ $product->name }}</div>
                                                <form
                                                    action="{{ route('account.compare.destroy', $product) }}"
                                                    method="POST"
                                                    class="mt-2"
                                                >
                                                    @csrf
                                                    @method('DELETE')
                
                                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                                        Remove
                                                    </button>
                                                </form>
                                            </td>
                                        @endforeach
                                    </tr>
                
                                    <tr>
                                        <th scope="row">Price</th>
                                        @foreach ($products as $product)
                                            <td class="text-center fw-bold">
                                                Rp {{ number_format($product->price, 0, ',', '.') }}
                                            </td>
                                        @endforeach
                                    </tr>
                
                                    <tr>
                                        <th scope="row">Category</th>
                                        @foreach ($products as $product)
                                            <td class="text-center">
                                                {{ $product->category?->name ?? '-' }}
                                            </td>
                                        @endforeach
                                    </tr>
                
                                    <tr>
                                        <th scope="row">Sub Category</th>
                                        @foreach ($products as $product)
                                            <td class="text-center">
                                                {{ $product->subcategory?->name ?? '-' }}
                                            </td>
                                        @endforeach
                                    </tr>
                
                                    <tr>
                                        <th scope="row">Stock</th>
                                        @foreach ($products as $product)
                                            <td class="text-center">
                                                {{ $product->stock > 0 ? 'Available' : 'Unavailable' }}
                                            </td>
                                        @endforeach
                                    </tr>
                
                                    <tr>
                                        <th scope="row">Description</th>
                                        @foreach ($products as $product)
                                            <td>
                                                {{ $product->description ? \Illuminate\Support\Str::limit($product->description, 120) : '-' }}
                                            </td>
                                        @endforeach
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif

            </div>
        </div>
    </div>
</section>
@endsection
@extends('layouts.storefront')

@section('title', 'Wishlist - MeiStore')

@section('content')
<section class="container py-4 py-lg-5">
    <div class="mb-4">
        <h1 class="section-title mb-1">Wishlist</h1>
        <p class="text-muted mb-0">
            Produk yang kamu simpan untuk dibeli nanti.
        </p>
    </div>

    @if ($products->isEmpty())
        <div class="alert alert-light border">
            <i class="bi bi-heart me-2"></i>
            Belum ada produk di wishlist.
        </div>
    @else
        <div class="row g-4">
            @foreach ($products as $product)
                <div class="col-6 col-md-4 col-lg-3">
                    <div class="product-card">
                        <a href="{{ route('storefront.product', $product->slug) }}">
                            @if ($product->image)
                                <img
                                    src="{{ $product->image }}"
                                    alt="{{ $product->name }}"
                                    class="product-image"
                                >
                            @else
                                <div class="product-image d-flex align-items-center justify-content-center">
                                    <i class="bi bi-image fs-1 text-secondary"></i>
                                </div>
                            @endif
                        </a>

                        <div class="product-body">
                            <div class="product-category">
                                {{ $product->category?->name ?? 'Tanpa kategori' }}
                            </div>

                            <a href="{{ route('storefront.product', $product->slug) }}">
                                <h3 class="product-name">
                                    {{ $product->name }}
                                </h3>
                            </a>

                            <div class="product-price">
                                Rp {{ number_format($product->price, 0, ',', '.') }}
                            </div>

                            <form
                                action="{{ route('wishlist.destroy', $product) }}"
                                method="POST"
                                class="mt-3"
                            >
                                @csrf
                                @method('DELETE')

                                <button class="btn btn-outline-danger w-100">
                                    <i class="bi bi-heart-fill me-1"></i>
                                    Hapus
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</section>
@endsection

@extends('layouts.storefront')

@section('title', 'Compare Produk - MeiStore')

@section('content')
<section class="container py-4 py-lg-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="section-title mb-1">Compare Product Compare</h1>
            <p class="text-muted mb-0">Compare product until 4 product</p>
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
            Unavailable product to compare.
        </div>
    @else
        <div class="table-respsonsive">
            <table class="table align-middle border">
                <tbody>
                    <tr>
                        <th>Product</th>

                        @foreach ($products as $product)
                            <td class="text-center" style="min-width:180px">
                                @if ($product->image)
                                    <img src="{{ $product->image }}"
                                        alt="{{ $product->name }}"
                                        class="img-fluid rounded mb-2"
                                        style="height:140px;object-fit:cover">
                                @endif

                                <div class="fw-bold">{{ $product->name }}</div>

                                <form action="{{ route('compare.destroy', $product) }}"
                                    method="POST" class="mt-2">
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

    <a href="{{ route('storefront.shop') }}" class="btn btn-primary mt-3">Back to Shop</a>
</section>
@endsection

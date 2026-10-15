@extends('layouts.storefront')

@section('title', config('app.name') . ' - Shop')

@section('content')
@php
    $purchasableVariants = $product->variants
        ->where('status', 'active')
        ->sortByDesc('is_default')
        ->values();

    $selectedVariant = $purchasableVariants->first(fn ($variant) => $variant->is_default) ?? $purchasableVariants->first();
    $selectedAvailable = $selectedVariant?->inventory ? $selectedVariant->inventory->available_quantity : (int) $product->stock;
@endphp
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
                    Shop
                </li>
            </ol>
        </nav>
    </div>
</div>

{{-- =========================================================
     SHOP HEADER
========================================================= --}}
<section class="store-section pb-3">
    <div class="container">
        <div class="store-section-header">
            <div>
                <h1 class="store-section-title">All Products</h1>
                <p class="store-section-subtitle">Find the best products here</p>
            </div>

            <div class="text-muted small">
                <i class="bi bi-box-seam me-1"></i>
                {{ $products->total() }} product
            </div>
        </div>
    </div>
</section>

{{-- =========================================================
     FILTER
========================================================= --}}
<section class="pb-4">
    <div class="container">
        <x-store-product-toolbar :categories="$categories" :action="route('storefront.shop')"/>
    </div>
</section>

{{-- =========================================================
     RESULT HEADER
========================================================= --}}
<section class="pb-3">
    <div class="container">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                @if ($products->total() > 0)
                    <span class="small text-muted">
                        Showing
                        <strong class="text-dark">{{ $products->firstItem() }}</strong>
                        -
                        <strong class="text-dark">{{ $products->lastItem() }}</strong>
                        of
                        <strong class="text-dark">{{ $products->total() }}</strong>
                        product
                    </span>
                @else
                    <span class="small text-muted">Product not found</span>
                @endif
            </div>
        </div>
    </div>
</section>


{{-- =========================================================
     PRODUCTS
========================================================= --}}
<section class="pb-5">
    <div class="container">
        @if ($products->count())
            <div class="row g-3 g-md-4">
                @foreach ($products as $product)
                    <div class="col-6 col-md-4 col-lg-3">
                        <x-store-product-card 
                            :product="$product" 
                            :purchasable-variants="$purchasableVariants"
                            :selectedAvailable="$selectedAvailable"
                        />
                    </div>
                @endforeach
            </div>
            {{-- =================================================
                 PAGINATION
            ================================================== --}}
            @if ($products->hasPages())
                <div class="store-pagination">
                    {{ $products->onEachSide(1)->links('pagination::bootstrap-5') }}
                </div>
            @endif
        @else
            {{-- Empty state --}}
            <div class="shop-empty-state">
                <div class="shop-empty-icon"><i class="bi bi-search"></i></div>

                <h2 class="h5 fw-bold mb-2">Product not found</h2>
                <p class="text-muted mb-4">No products match the selected filters.</p>
                <a
                    href="{{ route('storefront.shop') }}"
                    class="store-btn-primary"
                >
                    <i class="bi bi-arrow-counterclockwise"></i>
                    Reset Filter
                </a>
            </div>
        @endif
    </div>
</section>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const variantSelect = document.getElementById('product_variant_id');
    const quantityInput = document.getElementById('quantity');
    const priceElement = document.getElementById('selected-variant-price');
    const stockElement = document.getElementById('selected-variant-stock');
    const submitButton = document.getElementById('add-to-cart-submit');

    if (!variantSelect) {
        return;
    }

    function formatRupiah(value) {
        return 'Rp ' + new Intl.NumberFormat('id-ID', {
            maximumFractionDigits: 0
        }).format(Number(value || 0));
    }

    function updateVariant() {
        const option = variantSelect.selectedOptions[0];

        if (!option) {
            return;
        }

        const price = Number(option.dataset.price || 0);
        const stock = Number(option.dataset.stock || 0);

        priceElement.textContent = formatRupiah(price);
        quantityInput.max = Math.max(1, stock);

        if (Number(quantityInput.value) > stock) {
            quantityInput.value = Math.max(1, stock);
        }

        quantityInput.disabled = stock < 1;
        submitButton.disabled = stock < 1;

        stockElement.textContent = stock > 0
            ? 'Stok tersedia — ' + stock + ' unit'
            : 'Stok varian ini habis';
    }

    variantSelect.addEventListener('change', updateVariant);
    updateVariant();
});
</script>
@endpush
@endsection

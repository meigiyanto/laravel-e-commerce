@extends('layouts.storefront')

@section('title', 'Checkout - MeiStore')

@section('content')
<div class="container py-5">
    {{-- Header --}}
    <div class="mb-4">
        <h1 class="fw-bold mb-1">Checkout</h1>
        <p class="text-muted mb-0">Lengkapi informasi pengiriman untuk menyelesaikan pesanan.</p>
    </div>

    <form
        action="{{ route('checkout.store') }}"
        method="POST"
    >
        @csrf
        <div class="row g-4">
            {{-- LEFT --}}
            <div class="col-lg-7">
                {{-- Customer --}}
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body p-4">
                        <h5 class="fw-bold mb-4">
                            <i class="bi bi-person me-2"></i>
                            Informasi Penerima
                        </h5>

                        {{-- Name --}}
                        <div class="mb-3">
                            <label
                                for="customer_name"
                                class="form-label fw-semibold"
                            >
                                Nama Penerima
                            </label>

                            <input
                                type="text"
                                class="form-control @error('customer_name') is-invalid @enderror"
                                id="customer_name"
                                name="customer_name"
                                value="{{ old('customer_name', auth()->user()->name) }}"
                                placeholder="Masukkan nama penerima"
                                required
                            >

                            @error('customer_name')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror

                        </div>

                        {{-- Phone --}}
                        <div class="mb-3">

                            <label
                                for="phone"
                                class="form-label fw-semibold"
                            >
                                Nomor Telepon
                            </label>

                            <input
                                type="tel"
                                class="form-control @error('phone') is-invalid @enderror"
                                id="phone"
                                name="phone"
                                value="{{ old('phone') }}"
                                placeholder="08xxxxxxxxxx"
                                required
                            >

                            @error('phone')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror

                        </div>

                        {{-- Address --}}
                        <div class="mb-3">

                            <label
                                for="shipping_address"
                                class="form-label fw-semibold"
                            >
                                Alamat Pengiriman
                            </label>

                            <textarea
                                class="form-control @error('shipping_address') is-invalid @enderror"
                                id="shipping_address"
                                name="shipping_address"
                                rows="5"
                                placeholder="Nama jalan, nomor rumah, RT/RW, desa, kecamatan, kabupaten, provinsi, kode pos"
                                required
                            >{{ old('shipping_address') }}</textarea>

                            @error('shipping_address')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror

                        </div>

                        {{-- Notes --}}
                        <div class="mb-0">

                            <label
                                for="notes"
                                class="form-label fw-semibold"
                            >
                                Catatan
                                <span class="text-muted fw-normal">
                                    (opsional)
                                </span>
                            </label>

                            <textarea
                                class="form-control @error('notes') is-invalid @enderror"
                                id="notes"
                                name="notes"
                                rows="3"
                                placeholder="Contoh: Tolong kirim pada sore hari."
                            >{{ old('notes') }}</textarea>

                            @error('notes')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror

                        </div>
                    </div>
                </div>

                {{-- Payment Method --}}
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body p-4">
                        <h5 class="fw-bold mb-4">
                            <i class="bi bi-credit-card me-2"></i>
                            Metode Pembayaran
                        </h5>

                            {{-- Stripe --}}
                            <label
                                class="payment-method-option w-100"
                                for="payment_stripe"
                            >
                                <input
                                    type="radio"
                                    class="form-check-input me-2"
                                    name="payment_method"
                                    id="payment_stripe"
                                    value="stripe"
                                    {{ old('payment_method', 'stripe') === 'stripe' ? 'checked' : '' }}
                                >

                                <span>
                                    <strong class="d-block"><i class="bi bi-credit-card me-1"></i>Stripe</strong>
                                    <small class="text-muted">Bayar secara online menggunakan kartu atau metode pembayaran yang tersedia di Stripe.</small>
                                </span>
                            </label>

                            {{-- COD --}}
                            <label class="payment-method-option w-100" for="payment_cod">
                                <input
                                    type="radio"
                                    class="form-check-input me-2"
                                    name="payment_method"
                                    id="payment_cod"
                                    value="cod"
                                    {{ old('payment_method') === 'cod' ? 'checked' : '' }}>

                                <span>
                                    <strong class="d-block"><i class="bi bi-cash-coin me-1"></i>Cash on Delivery
                                    </strong><small class="text-muted">Bayar saat pesanan diterima</small>
                                </span>
                            </label>
                        @error('payment_method')
                            <div class="text-danger small mt-2">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>
                </div>
            </div>

            {{-- RIGHT --}}
            <div class="col-lg-5">

                <div
                    class="card border-0 shadow-sm"
                    style="position: sticky; top: 90px;"
                >

                    <div class="card-body p-4">

                        <h5 class="fw-bold mb-4">
                            Ringkasan Pesanan
                        </h5>

                        {{-- Products --}}
                        <div class="mb-4">
                            @foreach ($cart->items as $item)
                                <div class="d-flex gap-3 mb-3">

                                    <div
                                        class="rounded overflow-hidden flex-shrink-0"
                                        style="width: 64px; height: 64px;"
                                    >

                                        @if ($item->product->image)
                                            <img
                                                src="{{ asset('storage/' . $item->product->image) }}"
                                                alt="{{ $item->product->name }}"
                                                class="w-100 h-100"
                                                style="object-fit: cover;"
                                            >

                                        @else
                                            <div class="bg-light w-100 h-100 d-flex align-items-center justify-content-center">
                                                <i class="bi bi-image text-muted"></i>
                                            </div>
                                        @endif
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="fw-semibold">
                                            {{ $item->product->name }}
                                        </div>
                                        <div class="small text-muted">
                                            {{ $item->quantity }} ×
                                            Rp {{ number_format($item->product->price, 0, ',', '.') }}
                                        </div>
                                    </div>

                                    <div class="fw-semibold text-nowrap">

                                        Rp
                                        {{ number_format(
                                            $item->product->price * $item->quantity,
                                            0,
                                            ',',
                                            '.'
                                        ) }}

                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <hr>
                        {{-- Subtotal --}}
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Subtotal</span>
                            <span>Rp {{ number_format($subtotal, 0, ',', '.') }}</span>
                        </div>

                        {{-- Shipping --}}
                        <div class="d-flex justify-content-between mb-3">
                            <span class="text-muted">Ongkos Kirim</span>
                            <span class="text-success">Gratis</span>
                        </div>

                        <hr>

                        {{-- Total --}}
                        <div
                            class="d-flex justify-content-between align-items-center mb-4"
                        >

                            <span class="fs-5 fw-bold">Total</span>
                            <span class="fs-4 fw-bold text-primary">Rp {{ number_format($total, 0, ',', '.') }}</span>
                        </div>

                        {{-- Submit --}}
                        <button
                            type="submit"
                            class="btn btn-primary btn-lg w-100"
                        >
                            <i class="bi bi-check2-circle me-2"></i>
                            Buat Pesanan
                        </button>

                        <a
                            href="{{ route('cart.index') }}"
                            class="btn btn-outline-secondary w-100 mt-2"
                        >
                            Kembali ke Keranjang
                        </a>

                        <div class="text-center mt-3">
                            <small class="text-muted">
                                <i class="bi bi-shield-check me-1"></i>
                                Transaksi aman dan tercatat dalam sistem.
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

@push('styles')
<style>
    .payment-method-option {
        display: flex;
        align-items: flex-start;
        gap: 10px;
        padding: 16px;
        margin-bottom: 8px;
        border: 1px solid #dee2e6;
        border-radius: 10px;
        cursor: pointer;
        transition: border-color .2s ease,
                    background-color .2s ease;
    }

    .payment-method-option:hover {
        border-color: #0d6efd;
        background-color: #f8f9fa;
    }

    .payment-method-option:has(input:checked) {
        border-color: #0d6efd;
        background-color: #f0f6ff;
    }

    .payment-method-option input {
        margin-top: 3px;
    }
</style>
@endpush

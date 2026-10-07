@extends('layouts.storefront')

@section('title', config('app.name') . ' - Checkout Success')

@section('content')

<div class="container py-5">

    <div class="row justify-content-center">

        <div class="col-lg-8">

            <div class="card border-0 shadow-sm">

                <div class="card-body p-5 text-center">

                    <div
                        class="mx-auto mb-4 rounded-circle bg-success-subtle d-flex align-items-center justify-content-center"
                        style="width: 80px; height: 80px;"
                    >

                        <i
                            class="bi bi-check-lg text-success"
                            style="font-size: 2.5rem;"
                        ></i>

                    </div>

                    <h1 class="fw-bold mb-2">
                        Pesanan Berhasil!
                    </h1>

                    <p class="text-muted mb-4">
                        Terima kasih. Pesanan kamu telah berhasil dibuat.
                    </p>

                    <div class="bg-light rounded p-4 mb-4">

                        <div class="small text-muted mb-1">
                            Nomor Pesanan
                        </div>

                        <div class="fs-4 fw-bold">
                            {{ $order->order_number }}
                        </div>

                    </div>

                    {{-- Order information --}}
                    <div class="text-start mb-4">

                        <h5 class="fw-bold mb-3">
                            Detail Pesanan
                        </h5>

                        @foreach ($order->items as $item)

                            <div
                                class="d-flex justify-content-between border-bottom py-3"
                            >

                                <div>

                                    <div class="fw-semibold">
                                        {{ $item->product_name }}
                                    </div>

                                    <div class="small text-muted">
                                        {{ $item->quantity }} ×
                                        Rp {{ number_format($item->price, 0, ',', '.') }}
                                    </div>

                                </div>

                                <div class="fw-semibold">

                                    Rp
                                    {{ number_format(
                                        $item->subtotal,
                                        0,
                                        ',',
                                        '.'
                                    ) }}

                                </div>

                            </div>

                        @endforeach

                    </div>

                    {{-- Total --}}
                    <div
                        class="d-flex justify-content-between align-items-center mb-4"
                    >

                        <span class="fw-bold">
                            Total
                        </span>

                        <span class="fs-4 fw-bold text-primary">

                            Rp
                            {{ number_format(
                                $order->total,
                                0,
                                ',',
                                '.'
                            ) }}

                        </span>

                    </div>

                    {{-- Shipping --}}
                    <div class="text-start bg-light rounded p-3 mb-4">

                        <div class="fw-semibold mb-2">
                            <i class="bi bi-truck me-2"></i>
                            Alamat Pengiriman
                        </div>

                        <div>
                            {{ $order->customer_name }}
                        </div>

                        <div>
                            {{ $order->phone }}
                        </div>

                        <div class="text-muted">
                            {{ $order->shipping_address }}
                        </div>

                    </div>

                    <div class="d-flex flex-column flex-sm-row gap-2">

                        <a
                            href="{{ route('storefront.home') }}"
                            class="btn btn-primary flex-fill"
                        >
                            <i class="bi bi-house me-1"></i>
                            Kembali ke Home
                        </a>

                        <a
                            href="{{ route('storefront.shop') }}"
                            class="btn btn-outline-primary flex-fill"
                        >
                            Belanja Lagi
                        </a>

                    </div>

                </div>

            </div>

        </div>

    </div>

</div>

@endsection

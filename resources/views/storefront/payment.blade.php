@extends('layouts.app')

@section('title', 'Payment')

@section('content')

<div class="container py-5">

    <div class="row justify-content-center">

        <div class="col-lg-7">

            <div class="card card-bordered">

                <div class="card-inner">

                    <div class="text-center mb-4">
                        <h3 class="title">
                            Menunggu Pembayaran
                        </h3>

                        <p class="text-soft">
                            Order #{{ $order->order_number }}
                        </p>
                    </div>

                    <div class="alert alert-info">
                        Silakan lanjutkan pembayaran melalui
                        Xendit.
                    </div>

                    <div class="d-flex justify-content-between mb-2">
                        <span>Total Pesanan</span>

                        <strong>
                            Rp {{ number_format(
                                $order->total,
                                0,
                                ',',
                                '.'
                            ) }}
                        </strong>
                    </div>

                    <div class="d-grid mt-4">
                        <a
                            href="{{ $payment->payment_link_url }}"
                            class="btn btn-primary"
                        >
                            Lanjutkan Pembayaran
                        </a>
                    </div>

                    <div class="text-center mt-3">
                        <a
                            href="{{ route('orders.show', $order) }}"
                            class="text-soft"
                        >
                            Kembali ke detail pesanan
                        </a>
                    </div>

                </div>

            </div>

        </div>

    </div>

</div>

@endsection

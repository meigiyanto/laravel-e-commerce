@extends('layouts.storefront')

@section('title', 'Pembayaran - MeiStore')

@section('content')

<div class="container py-5">

    <div class="row justify-content-center">

        <div class="col-lg-7">

            <div class="card border-0 shadow-sm">

                <div class="card-body p-4">

                    <h3 class="fw-bold mb-1">
                        Pembayaran Pesanan
                    </h3>

                    <p class="text-muted">
                        {{ $order->order_number }}
                    </p>

                    <hr>

                    <div class="d-flex justify-content-between mb-3">
                        <span>Total Pembayaran</span>

                        <strong>
                            Rp {{ number_format($order->total, 0, ',', '.') }}
                        </strong>
                    </div>

                    <button
                        id="pay-button"
                        class="btn btn-primary w-100"
                    >
                        Bayar Sekarang
                    </button>

                </div>

            </div>

        </div>

    </div>

</div>

@endsection

@push('scripts')

<script
    src="https://app.sandbox.midtrans.com/snap/snap.js"
    data-client-key="{{ config('services.midtrans.client_key') }}">
</script>

<script>
document
    .getElementById('pay-button')
    .addEventListener('click', function () {

        snap.pay(@json($snapToken), {

            onSuccess: function () {
                window.location.href =
                    @json(route('orders.show', $order));
            },

            onPending: function () {
                window.location.href =
                    @json(route('orders.show', $order));
            },

            onError: function () {
                alert('Pembayaran gagal. Silakan coba lagi.');
            },

            onClose: function () {
                console.log('Payment popup ditutup.');
            }

        });

    });
</script>

@endpush

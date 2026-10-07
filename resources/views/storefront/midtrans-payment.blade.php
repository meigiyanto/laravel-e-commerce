@extends('layouts.storefront')

@section('title', config('app.name') . ' - Pembayaran Midtrans')

@section('content')

<div class="container py-5">

    <div class="row justify-content-center">

        <div class="col-lg-7">

            <div class="card border-0 shadow-sm">

                <div class="card-body p-4 p-lg-5">

                    <div class="text-center mb-4">

                        <h1 class="h3 fw-bold mb-2">
                            Pembayaran Pesanan
                        </h1>

                        <p class="text-muted mb-1">
                            Order #{{ $order->order_number }}
                        </p>

                        <div class="fs-4 fw-bold text-primary">
                            Rp {{ number_format(
                                $order->total,
                                0,
                                ',',
                                '.'
                            ) }}
                        </div>

                    </div>

                    <div id="payment-error" class="alert alert-danger d-none"></div>

                    <button id="pay-button" type="button" class="btn btn-primary btn-lg w-100">
                        <span id="button-text">
                            Bayar Sekarang
                        </span>

                        <span id="button-spinner" class="spinner-border spinner-border-sm d-none"></span>
                    </button>

                    <div class="text-center mt-3">

                        <a href="{{ route('orders.show', $order) }}" class="text-muted">
                            Kembali ke detail pesanan
                        </a>

                    </div>

                    <div class="alert alert-light border mt-4 mb-0 small">

                        <i class="bi bi-shield-check me-1"></i>

                        Pembayaran diproses secara aman oleh
                        Midtrans.

                    </div>

                </div>

            </div>

        </div>

    </div>

</div>

@endsection

@push('scripts')

<script src="{{ config('midtrans.is_production')
        ? 'https://app.midtrans.com/snap/snap.js'
        : 'https://app.sandbox.midtrans.com/snap/snap.js' }}" data-client-key="{{ $clientKey }}"></script>

<script>
document.addEventListener(
    'DOMContentLoaded',
    function() {

        const payButton =
            document.getElementById(
                'pay-button'
            );

        const buttonText =
            document.getElementById(
                'button-text'
            );

        const spinner =
            document.getElementById(
                'button-spinner'
            );

        const errorBox =
            document.getElementById(
                'payment-error'
            );

        payButton.addEventListener(
            'click',
            function() {

                setLoading(true);

                errorBox.classList.add(
                    'd-none'
                );

                window.snap.pay(
                    @json($snapToken), {

                        onSuccess: async function(
                            result
                        ) {

                            await verifyPayment(
                                result
                            );

                        },

                        onPending: async function(
                            result
                        ) {

                            await verifyPayment(
                                result
                            );

                        },

                        onError: function(
                            result
                        ) {

                            console.error(
                                'Midtrans error:',
                                result
                            );

                            showError(
                                'Pembayaran gagal diproses.'
                            );

                        },

                        onClose: function() {

                            setLoading(false);

                        },

                    }
                );

            }
        );

        async function verifyPayment(
            result
        ) {

            try {

                const response =
                    await fetch(
                        @json(
                            route(
                                'payment.midtrans.confirm',
                                $order
                            )
                        ), {
                            method: 'POST',

                            headers: {
                                'Content-Type': 'application/json',

                                'Accept': 'application/json',

                                'X-CSRF-TOKEN': @json(
                                    csrf_token()
                                ),
                            },

                            body: JSON.stringify({
                                transaction_id: result?.transaction_id ??
                                    null,
                            }),
                        }
                    );

                const data =
                    await response.json();

                if (
                    response.ok &&
                    data.success
                ) {

                    window.location.href =
                        data.redirect_url;

                    return;
                }

                /*
                 * Pending memang bukan error.
                 */
                if (
                    data.status === 'pending'
                ) {

                    window.location.href =
                        @json(
                            route(
                                'orders.show',
                                $order
                            )
                        );

                    return;
                }

                showError(
                    data.message ||
                    'Pembayaran belum berhasil diverifikasi.'
                );

            } catch (error) {

                console.error(
                    error
                );

                showError(
                    'Terjadi kesalahan saat memverifikasi pembayaran.'
                );
            }
        }

        function setLoading(
            loading
        ) {

            payButton.disabled =
                loading;

            spinner.classList.toggle(
                'd-none',
                !loading
            );

            buttonText.textContent =
                loading ?
                'Memproses...' :
                'Bayar Sekarang';
        }

        function showError(
            message
        ) {

            errorBox.textContent =
                message;

            errorBox.classList.remove(
                'd-none'
            );

            setLoading(false);
        }

    }
);
</script>

@endpush
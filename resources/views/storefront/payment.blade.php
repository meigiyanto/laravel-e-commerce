@extends('layouts.storefront')

@section('title', 'Pembayaran - MeiStore')

@section('content')

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4 p-lg-5">
                    <div class="text-center mb-4">
                        <h1 class="h3 fw-bold mb-2">Pembayaran Pesanan</h1>

                        <p class="text-muted mb-1">Order #{{ $order->order_number }}</p>

                        <div class="fs-4 fw-bold text-primary">
                            Rp {{ number_format(
                                $order->total,
                                0,
                                ',',
                                '.'
                            ) }}
                        </div>

                    </div>

                    <div
                        id="payment-error"
                        class="alert alert-danger d-none"
                    ></div>

                    <form id="payment-form">

                        <div class="mb-4">

                            <label class="form-label fw-semibold">
                                Metode Pembayaran
                            </label>

                            <div id="payment-element"></div>

                        </div>

                        <button
                            id="submit-payment"
                            type="submit"
                            class="btn btn-primary btn-lg w-100"
                        >

                            <span id="button-text">
                                Bayar Sekarang
                            </span>

                            <span
                                id="button-spinner"
                                class="spinner-border spinner-border-sm d-none"
                            ></span>

                        </button>

                    </form>

                    <div class="text-center mt-3">

                        <a
                            href="{{ route('orders.show', $order) }}"
                            class="text-muted"
                        >
                            Kembali ke detail pesanan
                        </a>

                    </div>

                    <div class="alert alert-light border mt-4 mb-0 small">

                        <i class="bi bi-shield-check me-1"></i>

                        Pembayaran diproses secara aman oleh Stripe.

                    </div>

                </div>

            </div>

        </div>

    </div>

</div>

@endsection


@push('scripts')

<script src="https://js.stripe.com/v3/"></script>

<script>
document.addEventListener(
    'DOMContentLoaded',
    async function () {

        const stripe = Stripe(
            @json($publishableKey)
        );

        const clientSecret =
            @json($clientSecret);

        const elements = stripe.elements({
            clientSecret: clientSecret,

            appearance: {
                theme: 'stripe',

                variables: {
                    colorPrimary: '#0d6efd',
                    borderRadius: '8px',
                },
            },
        });

        const paymentElement =
            elements.create('payment', {
                layout: 'accordion',
            });

        paymentElement.mount(
            '#payment-element'
        );

        const form =
            document.getElementById(
                'payment-form'
            );

        const submitButton =
            document.getElementById(
                'submit-payment'
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

        /*
        form.addEventListener(
            'submit',
            async function (event) {

                event.preventDefault();

                setLoading(true);

                errorBox.classList.add(
                    'd-none'
                );

                const {
                    error: submitError
                } = await elements.submit();

                if (submitError) {

                    showError(
                        submitError.message
                    );

                    return;
                }

                const {
                    error,
                    paymentIntent
                } = await stripe.confirmPayment({

                    elements,

                    clientSecret,

                    confirmParams: {
                        return_url:
                            @json(
                                route(
                                    'checkout.success',
                                    $order
                                )
                            ),

                        receipt_email:
                            @json(
                                $order->user?->email
                            ),
                    },

                    redirect: 'if_required',
                });

                if (error) {

                    showError(
                        error.message
                    );

                    return;
                }

                if (
                    !paymentIntent ||
                    !paymentIntent.id
                ) {

                    showError(
                        'PaymentIntent tidak ditemukan.'
                    );

                    return;
                }

                try {

                    const response =
                        await fetch(
                            @json(
                                route(
                                    'payment.confirm',
                                    $order
                                )
                            ),
                            {
                                method: 'POST',

                                headers: {
                                    'Content-Type':
                                        'application/json',

                                    'Accept':
                                        'application/json',

                                    'X-CSRF-TOKEN':
                                        @json(
                                            csrf_token()
                                        ),
                                },

                                body: JSON.stringify({
                                    payment_intent_id:
                                        paymentIntent.id,
                                }),
                            }
                        );

                    const data =
                        await response.json();

                    if (
                        !response.ok ||
                        !data.success
                    ) {

                        showError(
                            data.message ||
                            'Pembayaran belum berhasil diverifikasi.'
                        );

                        return;
                    }

                    window.location.href =
                        data.redirect_url;

                } catch (error) {

                    showError(
                        'Terjadi kesalahan saat memverifikasi pembayaran.'
                    );
                }

            });
        */

        form.addEventListener('submit', async function (event) {
            event.preventDefault();
            setLoading(true);
            errorBox.classList.add('d-none');

            try {
                const { error: submitError } = await elements.submit();

                if (submitError) {
                    throw new Error(submitError.message);
                }

                const { error, paymentIntent } = await stripe.confirmPayment({
                    elements,
                    clientSecret,
                    confirmParams: {
                        return_url: @json(route('checkout.success', $order)),
                        receipt_email: @json($order->user?->email),
                    },

                    redirect: 'if_required',
                });

                if (error) {
                    throw new Error(error.message);
                }

                if (!paymentIntent?.id) {
                    throw new Error('PaymentIntent tidak ditemukan.');
                }

                const response = await fetch(@json(route('payment.confirm', $order)), {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': @json(csrf_token()),
                        },

                        body: JSON.stringify({
                            payment_intent_id: paymentIntent.id,
                        }),
                    }
                );

                const data = await response.json();

                if (!response.ok || !data.success) {
                    throw new Error( data.message ||'Pembayaran belum berhasil diverifikasi.');
                }

                window.location.href = data.redirect_url;
            } catch (error) {
                console.error('Stripe payment error:',error);
                showError(error?.message || 'Terjadi kesalahan saat memproses pembayaran.');
            } finally {
                setLoading(false);
            }
        });

        function setLoading(loading) {
            submitButton.disabled = loading;
            spinner.classList.toggle('d-none', !loading);
            buttonText.textContent =  loading ? 'Memproses...' : 'Bayar Sekarang';
        }

        function showError(message) {
            errorBox.textContent =  message || 'Pembayaran gagal diproses.';
            errorBox.classList.remove('d-none');
            setLoading(false);
        }

    });
</script>

@endpush

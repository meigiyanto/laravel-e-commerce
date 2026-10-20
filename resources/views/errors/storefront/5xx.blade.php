@include('errors.partials.storefront', [
    'status' => $status ?? 500,
    'title' => 'Terjadi Kesalahan Server',
    'message' => 'Server mengalami kendala saat memproses permintaan Anda. Silakan coba lagi nanti.',
])
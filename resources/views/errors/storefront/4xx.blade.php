@include('errors.partials.storefront', [
    'status' => $status ?? 400,
    'title' => 'Permintaan Tidak Dapat Diproses',
    'message' => 'Permintaan Anda tidak dapat diproses. Periksa kembali informasi yang dikirim atau coba lagi.',
])
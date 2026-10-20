@include('errors.partials.dashboard', [
    'status' => 503,
    'title' => 'Layanan Sementara Tidak Tersedia',
    'message' => 'Dashboard sedang tidak dapat diakses. Silakan coba kembali beberapa saat lagi.',
])
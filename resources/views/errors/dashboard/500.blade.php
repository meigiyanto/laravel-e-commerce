@include('errors.partials.dashboard', [
    'status' => 500,
    'title' => 'Kesalahan Internal Server',
    'message' => 'Terjadi kesalahan saat memproses halaman administrasi. Silakan coba lagi nanti.',
])
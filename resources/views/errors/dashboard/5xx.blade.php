@include('errors.partials.dashboard', [
    'status' => $status ?? 500,
    'title' => 'Kesalahan Server',
    'message' => 'Server mengalami kendala saat memproses permintaan administrasi.',
])
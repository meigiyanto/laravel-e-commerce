@include('errors.partials.dashboard', [
    'status' => $status ?? 400,
    'title' => 'Permintaan Tidak Dapat Diproses',
    'message' => 'Permintaan tidak dapat diproses. Periksa kembali data atau hak akses Anda.',
])
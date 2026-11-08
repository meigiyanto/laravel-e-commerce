<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Operational Mode
    |--------------------------------------------------------------------------
    |
    | single_store: aplikasi berjalan sebagai satu toko mandiri.
    | multi_tenant: isolasi data dan konteks tenant diaktifkan.
    |
    */
    'mode' => env('APP_OPERATION_MODE', 'single_store'),
];
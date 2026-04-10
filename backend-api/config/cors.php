<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Menentukan operasi cross-origin apa saja yang boleh dieksekusi di browser.
    | Karena kita menghapus prefix /api, maka 'paths' harus diizinkan semua.
    |
    */

    // 🔥 FIX: Ganti 'api/*' menjadi '*' agar mencakup semua route tanpa prefix
    'paths' => ['*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    // 🔥 FIX: Masukkan URL frontend kamu agar lebih aman (localhost:3000)
    'allowed_origins' => ['http://localhost:3000'], 

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    // 🔥 FIX: Wajib TRUE jika kamu menggunakan Next-Auth atau Laravel Sanctum
    'supports_credentials' => true,

];
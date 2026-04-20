<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    // Mengizinkan semua path API dan CSRF cookie untuk Sanctum
    'paths' => ['api/*', 'sanctum/csrf-cookie', 'login', 'logout'],

    // Mengizinkan semua method (GET, POST, PUT, DELETE, dll)
    'allowed_methods' => ['*'],

    /*
    | 🔥 FIX CORS DISINI:
    | Menambahkan 127.0.0.1 karena browser sering membaca IP ini 
    | berbeda dengan 'localhost'
    */
    'allowed_origins' => [
        'http://localhost:3000', 
        'http://localhost:8000',
    ],

    'allowed_origins_patterns' => [],

    // Mengizinkan semua headers (Authorization, Content-Type, dll)
    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    /* | 🔥 PENTING:
    | Tetap true agar Token & Cookies bisa terkirim antara Next.js dan Laravel
    */
    'supports_credentials' => true,

];
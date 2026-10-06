<?php

/*
 * API dipakai server-ke-server (Akreditasi, Keuangan, LMS) sehingga CORS tertutup secara bawaan.
 * Isi CORS_ALLOWED_ORIGINS (dipisah koma) hanya bila ada klien peramban yang sah.
 */
return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],
    'allowed_methods' => ['GET', 'OPTIONS'],
    'allowed_origins' => array_values(array_filter(array_map('trim', explode(',', (string) env('CORS_ALLOWED_ORIGINS', ''))))),
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['Authorization', 'Accept', 'Content-Type'],
    'exposed_headers' => ['X-Api-Version'],
    'max_age' => 600,
    'supports_credentials' => false,
];

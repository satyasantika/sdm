<?php

/*
 * Pengerasan HTTP (F10.1). CSP dimulai dari mode report-only karena Filament/Livewire memakai skrip & gaya inline;
 * ubah CSP_MODE=enforce setelah diuji manual di staging (lihat docs/DEPLOY.md).
 */
return [
    // Autentikasi dua faktor (TOTP) panel admin. Dimatikan sementara; set MFA_AKTIF=true untuk mengaktifkan kembali.
    'mfa_aktif' => (bool) env('MFA_AKTIF', false),

    // off | report-only | enforce
    'csp_mode' => env('CSP_MODE', 'report-only'),

    'hsts_detik' => 31536000,

    'csp' => implode('; ', [
        "default-src 'self'",
        "script-src 'self' 'unsafe-inline' 'unsafe-eval'",
        "style-src 'self' 'unsafe-inline' https://fonts.bunny.net",
        "font-src 'self' data: https://fonts.bunny.net",
        "img-src 'self' data: blob: https:",
        "connect-src 'self'",
        "frame-ancestors 'self'",
        "base-uri 'self'",
        "form-action 'self'",
        "object-src 'none'",
    ]),
];

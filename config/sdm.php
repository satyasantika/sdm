<?php

return [
    // Domain surel yang boleh dipakai pengguna (akhiran, tanpa memandang huruf besar/kecil); dilonggarkan di lingkungan local.
    'domain_surel' => ['@unsil.ac.id', '@staff.unsil.ac.id'],

    'demo_password' => env('SEED_DEMO_PASSWORD'),

    'superadmin' => [
        'email' => env('SEED_SUPERADMIN_EMAIL'),
        'password' => env('SEED_SUPERADMIN_PASSWORD'),
    ],
];

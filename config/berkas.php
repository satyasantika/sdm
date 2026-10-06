<?php

/*
 * Kebijakan berkas: tautan, bukan unggahan (STANDAR-TEKNIS §1a). Server hanya menyimpan URL + metadata.
 */
return [
    'mode' => env('BERKAS_MODE', 'tautan'),

    'domain_diizinkan' => [
        'drive.google.com',
        'docs.google.com',
        '*.unsil.ac.id',
    ],

    'pemendek_ditolak' => ['bit.ly', 's.id', 'tinyurl.com', 't.co', 'goo.gl'],

    'jenis_sensitif' => ['sk', 'ijazah', 'transkrip', 'dokumen_identitas', 'dokumen_kepegawaian', 'bukti_usulan'],

    'timeout_cek_detik' => (int) env('BERKAS_TIMEOUT_CEK_DETIK', 10),

    'maks_redirect' => 3,

    'disk_tmp' => 'tmp',

    'umur_tmp_jam' => 24,
];

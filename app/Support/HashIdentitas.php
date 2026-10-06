<?php

namespace App\Support;

class HashIdentitas
{
    /** HMAC-SHA256 NIK (hanya digit) untuk pencarian & keunikan tanpa menyimpan NIK polos (BR-01). */
    public static function nik(string $nik): string
    {
        return hash_hmac('sha256', (string) preg_replace('/\D/', '', $nik), (string) config('app.key'));
    }
}

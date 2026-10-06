<?php

namespace App\Rules;

use App\Support\DriveUrl;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/** Validasi tautan berkas (BR-24): https, domain daftar putih, bukan pemendek URL, bukan folder. */
class TautanBerkasValid implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || $value === '') {
            return;
        }

        if (mb_strlen($value) > 2048) {
            $fail('Tautan terlalu panjang (maksimal 2048 karakter).');

            return;
        }

        $skema = strtolower((string) parse_url($value, PHP_URL_SCHEME));
        $host = DriveUrl::host($value);

        if ($skema !== 'https' || $host === null) {
            $fail('Tautan harus diawali https:// (mis. https://drive.google.com/file/d/...).');

            return;
        }

        foreach ((array) config('berkas.pemendek_ditolak') as $pemendek) {
            if ($host === $pemendek || str_ends_with($host, '.'.$pemendek)) {
                $fail('Pemendek URL tidak diperbolehkan. Gunakan tautan asli dari Google Drive.');

                return;
            }
        }

        if (! self::hostDiizinkan($host)) {
            $fail('Domain tidak diizinkan. Gunakan tautan Google Drive/Docs atau domain unsil.ac.id.');

            return;
        }

        if (DriveUrl::folder($value)) {
            $fail('Tautan folder tidak diperbolehkan. Bagikan tautan satu berkas, bukan folder.');
        }
    }

    public static function hostDiizinkan(string $host): bool
    {
        $host = strtolower($host);

        foreach ((array) config('berkas.domain_diizinkan') as $domain) {
            $domain = strtolower($domain);

            if (str_starts_with($domain, '*.')) {
                $dasar = substr($domain, 2);
                if ($host === $dasar || str_ends_with($host, '.'.$dasar)) {
                    return true;
                }
            } elseif ($host === $domain) {
                return true;
            }
        }

        return false;
    }
}

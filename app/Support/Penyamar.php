<?php

namespace App\Support;

/** Penyamaran data sensitif untuk tampilan (BR-16). */
class Penyamar
{
    public const KOSONG = '—';

    public static function nik(?string $nik): string
    {
        $digit = self::digit($nik);

        if ($digit === null) {
            return self::KOSONG;
        }

        return strlen($digit) <= 8
            ? str_repeat('*', strlen($digit))
            : substr($digit, 0, 4).str_repeat('*', strlen($digit) - 8).substr($digit, -4);
    }

    public static function npwp(?string $npwp): string
    {
        return self::empatTerakhir($npwp);
    }

    public static function rekening(?string $rekening): string
    {
        return self::empatTerakhir($rekening);
    }

    private static function empatTerakhir(?string $nilai): string
    {
        $digit = self::digit($nilai);

        return $digit === null ? self::KOSONG : '****'.substr($digit, -4);
    }

    private static function digit(?string $nilai): ?string
    {
        $digit = preg_replace('/\D/', '', (string) $nilai);

        return $digit === '' || $digit === null ? null : $digit;
    }
}

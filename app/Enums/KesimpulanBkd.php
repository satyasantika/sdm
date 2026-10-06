<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum KesimpulanBkd: string implements HasColor, HasLabel
{
    case Memenuhi = 'memenuhi';
    case TidakMemenuhi = 'tidak_memenuhi';
    case BelumDinilai = 'belum_dinilai';

    public function getLabel(): string
    {
        return match ($this) {
            self::Memenuhi => 'Memenuhi',
            self::TidakMemenuhi => 'Tidak memenuhi',
            self::BelumDinilai => 'Belum dinilai',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Memenuhi => 'success',
            self::TidakMemenuhi => 'danger',
            self::BelumDinilai => 'gray',
        };
    }

    /** Normalisasi teks sumber SISTER; istilah perlu diverifikasi bila format berubah. */
    public static function dariTeks(?string $teks): self
    {
        $norma = mb_strtolower(trim((string) $teks));
        $norma = preg_replace('/[\s._-]+/', ' ', $norma) ?? '';

        return match (true) {
            in_array($norma, ['m', 'memenuhi'], true) => self::Memenuhi,
            in_array($norma, ['t', 'tm', 'tidak memenuhi', 'tdk memenuhi'], true) => self::TidakMemenuhi,
            default => self::BelumDinilai,
        };
    }
}

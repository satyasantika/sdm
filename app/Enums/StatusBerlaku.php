<?php

namespace App\Enums;

use App\Support\Konfigurasi;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum StatusBerlaku: string implements HasColor, HasLabel
{
    case Berlaku = 'berlaku';
    case SegeraBerakhir = 'segera_berakhir';
    case Kedaluwarsa = 'kedaluwarsa';
    case TanpaBatas = 'tanpa_batas';

    public function getLabel(): string
    {
        return match ($this) {
            self::Berlaku => 'Berlaku',
            self::SegeraBerakhir => 'Segera berakhir',
            self::Kedaluwarsa => 'Kedaluwarsa',
            self::TanpaBatas => 'Tanpa batas',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Berlaku => 'success',
            self::SegeraBerakhir => 'warning',
            self::Kedaluwarsa => 'danger',
            self::TanpaBatas => 'gray',
        };
    }

    /** BR-20: null → tanpa batas; lewat → kedaluwarsa; ≤ ambang → segera berakhir; selain itu berlaku. */
    public static function dariTanggal(?CarbonInterface $kedaluwarsa): self
    {
        if ($kedaluwarsa === null) {
            return self::TanpaBatas;
        }

        $hariIni = CarbonImmutable::today();
        $tanggal = CarbonImmutable::parse($kedaluwarsa)->startOfDay();

        if ($tanggal->lessThan($hariIni)) {
            return self::Kedaluwarsa;
        }

        $ambang = (int) Konfigurasi::get('ambang_segera_berakhir_hari', 90);

        return $tanggal->lessThanOrEqualTo($hariIni->addDays($ambang)) ? self::SegeraBerakhir : self::Berlaku;
    }
}

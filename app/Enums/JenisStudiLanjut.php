<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum JenisStudiLanjut: string implements HasLabel
{
    case TugasBelajar = 'tugas_belajar';
    case IzinBelajar = 'izin_belajar';

    public function getLabel(): string
    {
        return match ($this) {
            self::TugasBelajar => 'Tugas belajar',
            self::IzinBelajar => 'Izin belajar',
        };
    }
}

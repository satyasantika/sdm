<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum KelompokJabatan: string implements HasLabel
{
    case Dosen = 'dosen';
    case Tendik = 'tendik';

    public function getLabel(): string
    {
        return match ($this) {
            self::Dosen => 'Dosen',
            self::Tendik => 'Tenaga Kependidikan',
        };
    }
}

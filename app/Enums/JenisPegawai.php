<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum JenisPegawai: string implements HasColor, HasLabel
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

    public function getColor(): string
    {
        return match ($this) {
            self::Dosen => 'primary',
            self::Tendik => 'info',
        };
    }
}

<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum JenisKelamin: string implements HasLabel
{
    case Laki = 'L';
    case Perempuan = 'P';

    public function getLabel(): string
    {
        return match ($this) {
            self::Laki => 'Laki-laki',
            self::Perempuan => 'Perempuan',
        };
    }
}

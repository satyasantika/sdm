<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum JenisGolongan: string implements HasLabel
{
    case Pns = 'pns';
    case Pppk = 'pppk';

    public function getLabel(): string
    {
        return match ($this) {
            self::Pns => 'PNS',
            self::Pppk => 'PPPK',
        };
    }
}

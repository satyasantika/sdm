<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum HubunganKeluarga: string implements HasLabel
{
    case Suami = 'suami';
    case Istri = 'istri';
    case Anak = 'anak';
    case Ayah = 'ayah';
    case Ibu = 'ibu';

    public function getLabel(): string
    {
        return match ($this) {
            self::Suami => 'Suami',
            self::Istri => 'Istri',
            self::Anak => 'Anak',
            self::Ayah => 'Ayah',
            self::Ibu => 'Ibu',
        };
    }

    public function pasangan(): bool
    {
        return in_array($this, [self::Suami, self::Istri], true);
    }
}

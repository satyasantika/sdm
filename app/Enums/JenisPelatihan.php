<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum JenisPelatihan: string implements HasLabel
{
    case Pelatihan = 'pelatihan';
    case Workshop = 'workshop';
    case Seminar = 'seminar';
    case DiklatStruktural = 'diklat_struktural';
    case DiklatFungsional = 'diklat_fungsional';
    case PekertiAa = 'pekerti_aa';

    public function getLabel(): string
    {
        return match ($this) {
            self::Pelatihan => 'Pelatihan',
            self::Workshop => 'Workshop',
            self::Seminar => 'Seminar',
            self::DiklatStruktural => 'Diklat struktural',
            self::DiklatFungsional => 'Diklat fungsional',
            self::PekertiAa => 'Pekerti/AA',
        };
    }
}

<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum TingkatKegiatan: string implements HasLabel
{
    case Prodi = 'prodi';
    case Fakultas = 'fakultas';
    case Universitas = 'universitas';
    case Daerah = 'daerah';
    case Nasional = 'nasional';
    case Internasional = 'internasional';

    public function getLabel(): string
    {
        return match ($this) {
            self::Prodi => 'Program studi',
            self::Fakultas => 'Fakultas',
            self::Universitas => 'Universitas',
            self::Daerah => 'Daerah',
            self::Nasional => 'Nasional',
            self::Internasional => 'Internasional',
        };
    }
}

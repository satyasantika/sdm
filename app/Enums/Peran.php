<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum Peran: string implements HasLabel
{
    case SuperAdmin = 'super-admin';
    case AdminKepegawaian = 'admin-kepegawaian';
    case AdminProdi = 'admin-prodi';
    case Pimpinan = 'pimpinan';
    case Dosen = 'dosen';
    case Tendik = 'tendik';
    case KlienApi = 'klien-api';

    public function getLabel(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Super Admin',
            self::AdminKepegawaian => 'Admin Kepegawaian',
            self::AdminProdi => 'Admin Prodi',
            self::Pimpinan => 'Pimpinan',
            self::Dosen => 'Dosen',
            self::Tendik => 'Tenaga Kependidikan',
            self::KlienApi => 'Klien API',
        };
    }

    /** @return list<self> */
    public static function panelAdmin(): array
    {
        return [self::SuperAdmin, self::AdminKepegawaian, self::AdminProdi, self::Pimpinan];
    }
}

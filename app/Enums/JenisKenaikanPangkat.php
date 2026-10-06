<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum JenisKenaikanPangkat: string implements HasLabel
{
    case PengangkatanCpns = 'pengangkatan_cpns';
    case PengangkatanPns = 'pengangkatan_pns';
    case PengangkatanPppk = 'pengangkatan_pppk';
    case Reguler = 'reguler';
    case PilihanJabatanFungsional = 'pilihan_jabatan_fungsional';
    case PenyesuaianIjazah = 'penyesuaian_ijazah';
    case Lainnya = 'lainnya';

    public function getLabel(): string
    {
        return match ($this) {
            self::PengangkatanCpns => 'Pengangkatan CPNS',
            self::PengangkatanPns => 'Pengangkatan PNS',
            self::PengangkatanPppk => 'Pengangkatan PPPK',
            self::Reguler => 'Reguler',
            self::PilihanJabatanFungsional => 'Pilihan (jabatan fungsional)',
            self::PenyesuaianIjazah => 'Penyesuaian ijazah',
            self::Lainnya => 'Lainnya',
        };
    }
}

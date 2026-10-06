<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum JenisPengingat: string implements HasLabel
{
    case KenaikanPangkat = 'kenaikan_pangkat';
    case Kgb = 'kgb';
    case KenaikanJabfung = 'kenaikan_jabfung';
    case Pensiun = 'pensiun';
    case DokumenKedaluwarsa = 'dokumen_kedaluwarsa';
    case SertifikasiKedaluwarsa = 'sertifikasi_kedaluwarsa';
    case StudiLanjutBerakhir = 'studi_lanjut_berakhir';

    public function getLabel(): string
    {
        return match ($this) {
            self::KenaikanPangkat => 'Kenaikan pangkat',
            self::Kgb => 'Kenaikan gaji berkala',
            self::KenaikanJabfung => 'Kenaikan jabatan fungsional',
            self::Pensiun => 'Pensiun',
            self::DokumenKedaluwarsa => 'Dokumen kedaluwarsa',
            self::SertifikasiKedaluwarsa => 'Sertifikasi kedaluwarsa',
            self::StudiLanjutBerakhir => 'Studi lanjut berakhir',
        };
    }
}

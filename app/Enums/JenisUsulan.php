<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum JenisUsulan: string implements HasLabel
{
    case UbahBiodata = 'ubah_biodata';
    case TambahRiwayat = 'tambah_riwayat';
    case UbahRiwayat = 'ubah_riwayat';
    case HapusRiwayat = 'hapus_riwayat';

    public function getLabel(): string
    {
        return match ($this) {
            self::UbahBiodata => 'Ubah biodata',
            self::TambahRiwayat => 'Tambah riwayat',
            self::UbahRiwayat => 'Ubah riwayat',
            self::HapusRiwayat => 'Hapus riwayat',
        };
    }
}

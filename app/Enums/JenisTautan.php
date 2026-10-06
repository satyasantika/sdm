<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum JenisTautan: string implements HasLabel
{
    case Sk = 'sk';
    case Ijazah = 'ijazah';
    case Transkrip = 'transkrip';
    case Sertifikat = 'sertifikat';
    case Penghargaan = 'penghargaan';
    case Pelatihan = 'pelatihan';
    case DokumenIdentitas = 'dokumen_identitas';
    case DokumenKepegawaian = 'dokumen_kepegawaian';
    case BuktiUsulan = 'bukti_usulan';
    case Foto = 'foto';
    case Lainnya = 'lainnya';

    public function getLabel(): string
    {
        return match ($this) {
            self::Sk => 'SK',
            self::Ijazah => 'Ijazah',
            self::Transkrip => 'Transkrip',
            self::Sertifikat => 'Sertifikat',
            self::Penghargaan => 'Penghargaan',
            self::Pelatihan => 'Pelatihan',
            self::DokumenIdentitas => 'Dokumen identitas',
            self::DokumenKepegawaian => 'Dokumen kepegawaian',
            self::BuktiUsulan => 'Bukti usulan',
            self::Foto => 'Foto',
            self::Lainnya => 'Lainnya',
        };
    }

    /** BR-30: daftar jenis sensitif dibaca dari config/berkas.php. */
    public function isSensitif(): bool
    {
        return in_array($this->value, (array) config('berkas.jenis_sensitif'), true);
    }
}

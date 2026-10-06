<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum StatusAktifPegawai: string implements HasColor, HasLabel
{
    case Aktif = 'aktif';
    case TugasBelajar = 'tugas_belajar';
    case CutiDiLuarTanggungan = 'cuti_di_luar_tanggungan';
    case Pindah = 'pindah';
    case Diberhentikan = 'diberhentikan';
    case Pensiun = 'pensiun';
    case Meninggal = 'meninggal';

    public function getLabel(): string
    {
        return match ($this) {
            self::Aktif => 'Aktif',
            self::TugasBelajar => 'Tugas belajar',
            self::CutiDiLuarTanggungan => 'Cuti di luar tanggungan',
            self::Pindah => 'Pindah',
            self::Diberhentikan => 'Diberhentikan',
            self::Pensiun => 'Pensiun',
            self::Meninggal => 'Meninggal',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Aktif => 'success',
            self::TugasBelajar, self::CutiDiLuarTanggungan => 'warning',
            self::Pindah, self::Pensiun => 'gray',
            self::Diberhentikan, self::Meninggal => 'danger',
        };
    }

    /** Transisi sesuai diagram PRD 7.3. */
    public function bolehBerpindahKe(self $ke): bool
    {
        return in_array($ke, match ($this) {
            self::Aktif => [self::TugasBelajar, self::CutiDiLuarTanggungan, self::Pindah, self::Diberhentikan, self::Pensiun, self::Meninggal],
            self::TugasBelajar => [self::Aktif, self::Diberhentikan],
            self::CutiDiLuarTanggungan => [self::Aktif],
            default => [],
        }, true);
    }

    /** @return list<self> */
    public function tujuanTersedia(): array
    {
        return array_values(array_filter(self::cases(), fn (self $s): bool => $this->bolehBerpindahKe($s)));
    }
}

<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum StatusPengingat: string implements HasColor, HasLabel
{
    case Aktif = 'aktif';
    case LewatTempo = 'lewat_tempo';
    case Ditindaklanjuti = 'ditindaklanjuti';
    case Diabaikan = 'diabaikan';
    case Selesai = 'selesai';

    public function getLabel(): string
    {
        return match ($this) {
            self::Aktif => 'Aktif',
            self::LewatTempo => 'Lewat tempo',
            self::Ditindaklanjuti => 'Ditindaklanjuti',
            self::Diabaikan => 'Diabaikan',
            self::Selesai => 'Selesai',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Aktif => 'info',
            self::LewatTempo => 'danger',
            self::Ditindaklanjuti => 'warning',
            self::Diabaikan => 'gray',
            self::Selesai => 'success',
        };
    }

    /** Transisi sesuai diagram PRD 7.2. */
    public function bolehBerpindahKe(self $ke): bool
    {
        return in_array($ke, match ($this) {
            self::Aktif => [self::Ditindaklanjuti, self::Diabaikan, self::Selesai, self::LewatTempo],
            self::LewatTempo => [self::Ditindaklanjuti, self::Selesai],
            self::Ditindaklanjuti => [self::Selesai],
            default => [],
        }, true);
    }

    /** Pengingat yang masih berjalan (belum final). */
    public function berjalan(): bool
    {
        return in_array($this, [self::Aktif, self::LewatTempo, self::Ditindaklanjuti], true);
    }

    /** Layak dikirim sebagai notifikasi. */
    public function perluDikirim(): bool
    {
        return in_array($this, [self::Aktif, self::LewatTempo], true);
    }

    /** @return list<string> */
    public static function nilaiBerjalan(): array
    {
        return [self::Aktif->value, self::LewatTempo->value, self::Ditindaklanjuti->value];
    }
}

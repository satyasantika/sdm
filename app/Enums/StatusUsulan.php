<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum StatusUsulan: string implements HasColor, HasLabel
{
    case Draf = 'draf';
    case Diajukan = 'diajukan';
    case Dikembalikan = 'dikembalikan';
    case Disetujui = 'disetujui';
    case Ditolak = 'ditolak';
    case Dibatalkan = 'dibatalkan';

    public function getLabel(): string
    {
        return match ($this) {
            self::Draf => 'Draf',
            self::Diajukan => 'Diajukan',
            self::Dikembalikan => 'Dikembalikan',
            self::Disetujui => 'Disetujui',
            self::Ditolak => 'Ditolak',
            self::Dibatalkan => 'Dibatalkan',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Draf => 'gray',
            self::Diajukan => 'info',
            self::Dikembalikan => 'warning',
            self::Disetujui => 'success',
            self::Ditolak => 'danger',
            self::Dibatalkan => 'gray',
        };
    }

    /** Usulan yang masih berjalan (menahan kunci_aktif). */
    public function isAktif(): bool
    {
        return in_array($this, [self::Draf, self::Diajukan, self::Dikembalikan], true);
    }

    public function isFinal(): bool
    {
        return ! $this->isAktif();
    }

    /** Transisi sesuai diagram PRD 7.1. */
    public function bolehBerpindahKe(self $ke): bool
    {
        return in_array($ke, match ($this) {
            self::Draf => [self::Diajukan, self::Dibatalkan],
            self::Diajukan => [self::Disetujui, self::Ditolak, self::Dikembalikan, self::Dibatalkan],
            self::Dikembalikan => [self::Diajukan, self::Dibatalkan],
            default => [],
        }, true);
    }
}

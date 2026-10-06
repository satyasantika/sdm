<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum StatusStudiLanjut: string implements HasColor, HasLabel
{
    case Berjalan = 'berjalan';
    case Diperpanjang = 'diperpanjang';
    case Selesai = 'selesai';
    case Berhenti = 'berhenti';

    public function getLabel(): string
    {
        return match ($this) {
            self::Berjalan => 'Berjalan',
            self::Diperpanjang => 'Diperpanjang',
            self::Selesai => 'Selesai',
            self::Berhenti => 'Berhenti',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Berjalan, self::Diperpanjang => 'info',
            self::Selesai => 'success',
            self::Berhenti => 'danger',
        };
    }

    public function berlangsung(): bool
    {
        return in_array($this, [self::Berjalan, self::Diperpanjang], true);
    }
}

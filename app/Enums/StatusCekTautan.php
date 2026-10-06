<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum StatusCekTautan: string implements HasColor, HasLabel
{
    case Belum = 'belum';
    case DapatDiakses = 'dapat_diakses';
    case Terbatas = 'terbatas';
    case TidakDapatDiakses = 'tidak_dapat_diakses';
    case TerlaluTerbuka = 'terlalu_terbuka';

    public function getLabel(): string
    {
        return match ($this) {
            self::Belum => 'Belum dicek',
            self::DapatDiakses => 'Dapat diakses',
            self::Terbatas => 'Terbatas',
            self::TidakDapatDiakses => 'Tidak dapat diakses',
            self::TerlaluTerbuka => 'Terlalu terbuka',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Belum => 'gray',
            self::DapatDiakses, self::Terbatas => 'success',
            self::TidakDapatDiakses => 'danger',
            self::TerlaluTerbuka => 'warning',
        };
    }

    public function bermasalah(): bool
    {
        return in_array($this, [self::TidakDapatDiakses, self::TerlaluTerbuka], true);
    }
}

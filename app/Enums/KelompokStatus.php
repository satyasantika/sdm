<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum KelompokStatus: string implements HasLabel
{
    case Asn = 'asn';
    case NonAsn = 'non_asn';

    public function getLabel(): string
    {
        return match ($this) {
            self::Asn => 'ASN',
            self::NonAsn => 'Non-ASN',
        };
    }
}

<?php

namespace App\Models\Concerns;

use App\Support\Penyamar;
use Illuminate\Database\Eloquent\Casts\Attribute;

/**
 * @property-read string $nik_tersamar
 * @property-read string $npwp_tersamar
 * @property-read string $rekening_tersamar
 */
trait MenyamarkanDataSensitif
{
    protected function nikTersamar(): Attribute
    {
        return Attribute::get(fn (): string => Penyamar::nik($this->nik));
    }

    protected function npwpTersamar(): Attribute
    {
        return Attribute::get(fn (): string => Penyamar::npwp($this->npwp));
    }

    protected function rekeningTersamar(): Attribute
    {
        return Attribute::get(fn (): string => Penyamar::rekening($this->nomor_rekening));
    }
}

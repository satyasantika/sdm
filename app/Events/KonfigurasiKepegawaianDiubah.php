<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;

/** Dikirim bila konfigurasi yang memengaruhi pengingat/pensiun berubah (listener di F8). */
class KonfigurasiKepegawaianDiubah
{
    use Dispatchable;

    /** @param  list<string>  $kunci */
    public function __construct(public readonly array $kunci) {}
}

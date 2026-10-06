<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;

/** Dikirim saat data pegawai/riwayat yang memengaruhi statistik berubah; menghapus cache dasbor terkait. */
class DataPegawaiBerubah
{
    use Dispatchable;

    public function __construct(public readonly ?string $prodiId = null) {}
}

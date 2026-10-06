<?php

namespace App\Actions\Riwayat;

use App\Models\Pegawai;
use App\Models\RiwayatPangkat;

/** BR-08: menandai pangkat terkini dan menyalin golongan ringkas ke pegawai. */
class SinkronkanPangkatTerkini
{
    public function handle(Pegawai $pegawai): ?RiwayatPangkat
    {
        $terkini = RiwayatPangkat::tandaiTerkini($pegawai->getKey());

        $pegawai->forceFill(['golongan_id' => $terkini?->golongan_id])->save();

        return $terkini;
    }
}

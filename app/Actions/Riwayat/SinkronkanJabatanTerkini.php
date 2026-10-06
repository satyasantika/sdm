<?php

namespace App\Actions\Riwayat;

use App\Models\Pegawai;
use App\Models\RiwayatJabatanFungsional;

/** BR-08: menandai riwayat terkini dan menyalin jabatan ringkas ke pegawai (memicu hitung ulang pensiun). */
class SinkronkanJabatanTerkini
{
    public function handle(Pegawai $pegawai): ?RiwayatJabatanFungsional
    {
        $terkini = RiwayatJabatanFungsional::tandaiTerkini($pegawai->getKey());

        $pegawai->jabatan_fungsional_id = $terkini?->jabatan_fungsional_id;
        $pegawai->save();

        return $terkini;
    }
}

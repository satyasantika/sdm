<?php

namespace App\Observers;

use App\Actions\Pegawai\HitungTanggalPensiun;
use App\Models\Pegawai;

class PegawaiObserver
{
    public function saving(Pegawai $pegawai): void
    {
        if (! $pegawai->exists || $pegawai->isDirty(['tanggal_lahir', 'jenis_pegawai', 'jabatan_fungsional_id'])) {
            $pegawai->tanggal_pensiun = app(HitungTanggalPensiun::class)->handle($pegawai);
        }
    }
}

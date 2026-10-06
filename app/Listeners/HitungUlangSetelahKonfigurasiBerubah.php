<?php

namespace App\Listeners;

use App\Events\KonfigurasiKepegawaianDiubah;
use App\Jobs\HitungUlangPengingat;
use App\Jobs\HitungUlangTanggalPensiun;

/** BR-28: perubahan BUP/interval/pembulatan memicu hitung ulang pensiun dan pengingat. */
class HitungUlangSetelahKonfigurasiBerubah
{
    public function handle(KonfigurasiKepegawaianDiubah $event): void
    {
        HitungUlangTanggalPensiun::dispatch();
        HitungUlangPengingat::dispatch();
    }
}

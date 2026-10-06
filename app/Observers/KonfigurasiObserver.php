<?php

namespace App\Observers;

use App\Events\KonfigurasiKepegawaianDiubah;
use App\Models\Konfigurasi;
use App\Support\Konfigurasi as KonfigurasiCache;

class KonfigurasiObserver
{
    /** Kunci yang memengaruhi pensiun dan pengingat (BR-28). */
    private const KUNCI_MEMICU = [
        'bup_dosen', 'bup_profesor', 'bup_tendik', 'pembulatan_tmt_pensiun', 'interval_kp_bulan', 'interval_kgb_bulan',
        'tahap_pengingat_hari', 'cakrawala_pengingat_hari', 'ambang_segera_berakhir_hari',
    ];

    public function saved(Konfigurasi $konfigurasi): void
    {
        KonfigurasiCache::lupakan();

        if ($konfigurasi->wasChanged('nilai') && in_array($konfigurasi->kunci, self::KUNCI_MEMICU, true)) {
            KonfigurasiKepegawaianDiubah::dispatch([$konfigurasi->kunci]);
        }
    }

    public function deleted(Konfigurasi $konfigurasi): void
    {
        KonfigurasiCache::lupakan();
    }
}

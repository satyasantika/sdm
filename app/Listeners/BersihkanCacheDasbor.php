<?php

namespace App\Listeners;

use App\Actions\Laporan\HitungStatistikDasbor;
use App\Events\DataPegawaiBerubah;
use App\Jobs\SegarkanStatistikDasbor;
use App\Models\Semester;
use Illuminate\Support\Facades\Cache;

/** Menghapus cache statistik terkait; penghangatan ulang didebounce 60 detik. */
class BersihkanCacheDasbor
{
    public function handle(DataPegawaiBerubah $event): void
    {
        Cache::forget(HitungStatistikDasbor::kunci(null));

        if ($event->prodiId) {
            Cache::forget(HitungStatistikDasbor::kunci($event->prodiId));
            Cache::forget('sdm:statistik:syarat-unggul:'.$event->prodiId);
        }

        Cache::forget('sdm:statistik:syarat-unggul:fakultas');

        foreach (Semester::query()->pluck('id') as $semesterId) {
            Cache::forget('sdm:dasbor:rasio:'.$semesterId);
        }

        if (Cache::add('sdm:dasbor:segarkan-terjadwal', 1, 60)) {
            SegarkanStatistikDasbor::dispatch()->delay(now()->addSeconds(60));
        }
    }
}

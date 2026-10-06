<?php

namespace App\Jobs;

use App\Actions\Laporan\HitungStatistikDasbor;
use App\Models\Prodi;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;

/** Menghangatkan cache statistik dasbor (fakultas dan tiap prodi aktif). */
class SegarkanStatistikDasbor implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public function __construct()
    {
        $this->onQueue('default');
    }

    public function handle(HitungStatistikDasbor $hitung): void
    {
        $hitung->handle(null);

        foreach (Prodi::query()->where('is_aktif', true)->pluck('id') as $prodiId) {
            $hitung->handle((string) $prodiId);
        }

        Cache::forget('sdm:dasbor:segarkan-terjadwal');
    }
}

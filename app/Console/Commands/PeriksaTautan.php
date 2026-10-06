<?php

namespace App\Console\Commands;

use App\Jobs\PeriksaTautanBerkas;
use App\Models\TautanBerkas;
use App\Support\Konfigurasi;
use Illuminate\Console\Command;

class PeriksaTautan extends Command
{
    protected $signature = 'sdm:periksa-tautan';

    protected $description = 'Antrekan pemeriksaan keteraksesan tautan berkas (sensitif lebih dahulu), sesuai interval konfigurasi (BR-29)';

    public function handle(): int
    {
        $batas = now()->subDays((int) Konfigurasi::get('interval_periksa_tautan_hari', 7));
        $jumlah = 0;

        TautanBerkas::query()
            ->where(fn ($q) => $q->whereNull('dicek_pada')->orWhere('dicek_pada', '<=', $batas))
            ->orderByDesc('is_sensitif')
            ->orderBy('dicek_pada')
            ->select('id')
            ->chunkById(200, function ($kelompok) use (&$jumlah): void {
                foreach ($kelompok as $tautan) {
                    PeriksaTautanBerkas::dispatch((string) $tautan->getKey());
                    $jumlah++;
                }
            });

        $this->info("{$jumlah} tautan diantrekan untuk diperiksa.");

        return self::SUCCESS;
    }
}

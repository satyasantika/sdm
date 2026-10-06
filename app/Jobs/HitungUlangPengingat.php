<?php

namespace App\Jobs;

use App\Actions\Pengingat\HitungPengingatPegawai;
use App\Enums\StatusAktifPegawai;
use App\Models\Pegawai;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/** Menghitung ulang pengingat semua pegawai aktif per chunk 100 (antrean default). */
class HitungUlangPengingat implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $timeout = 600;

    public int $uniqueFor = 600;

    public function __construct()
    {
        $this->onQueue('default');
    }

    public function handle(HitungPengingatPegawai $hitung): void
    {
        Pegawai::query()
            ->whereIn('status_aktif', [StatusAktifPegawai::Aktif->value, StatusAktifPegawai::TugasBelajar->value])
            ->with('statusKepegawaian')
            ->chunkById(100, function ($kelompok) use ($hitung): void {
                foreach ($kelompok as $pegawai) {
                    $hitung->handle($pegawai);
                }
            });
    }
}

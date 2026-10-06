<?php

namespace App\Jobs;

use App\Actions\Pegawai\HitungTanggalPensiun;
use App\Models\Pegawai;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;

/** BR-28: menghitung ulang tanggal_pensiun semua pegawai setelah BUP/pembulatan/jabatan puncak berubah. */
class HitungUlangTanggalPensiun implements ShouldQueue
{
    use Queueable;

    public int $timeout = 600;

    public function __construct()
    {
        $this->onQueue('default');
    }

    public function handle(HitungTanggalPensiun $hitung): void
    {
        $lock = Cache::lock('sdm:lock:hitung-ulang-pensiun', 600);

        if (! $lock->get()) {
            return;
        }

        try {
            Pegawai::query()->chunkById(100, function ($kelompok) use ($hitung): void {
                foreach ($kelompok as $pegawai) {
                    $baru = $hitung->handle($pegawai)?->toDateString();

                    if ($baru !== $pegawai->tanggal_pensiun?->toDateString()) {
                        $pegawai->forceFill(['tanggal_pensiun' => $baru])->saveQuietly();
                    }
                }
            });
        } finally {
            $lock->release();
        }

        HitungUlangPengingat::dispatch();
    }
}

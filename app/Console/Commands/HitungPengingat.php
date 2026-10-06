<?php

namespace App\Console\Commands;

use App\Jobs\HitungUlangPengingat;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class HitungPengingat extends Command
{
    protected $signature = 'sdm:hitung-pengingat';

    protected $description = 'Hitung ulang pengingat tenggat semua pegawai aktif (antrean)';

    public function handle(): int
    {
        $lock = Cache::lock('sdm:lock:pengingat-harian', 1800);

        if (! $lock->get()) {
            $this->warn('Perhitungan pengingat sedang berjalan; dilewati.');

            return self::SUCCESS;
        }

        try {
            HitungUlangPengingat::dispatch();
            $this->info('Job HitungUlangPengingat dikirim ke antrean.');
        } finally {
            $lock->release();
        }

        return self::SUCCESS;
    }
}

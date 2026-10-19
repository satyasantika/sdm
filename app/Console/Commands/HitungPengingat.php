<?php

namespace App\Console\Commands;

use App\Jobs\HitungUlangPengingat;
use Illuminate\Console\Command;

class HitungPengingat extends Command
{
    protected $signature = 'sdm:hitung-pengingat';

    protected $description = 'Hitung ulang pengingat tenggat semua pegawai aktif (antrean)';

    /** Tumpang tindih dicegah jadwal (withoutOverlapping + onOneServer di routes/console.php), bukan kunci khusus (§1b). */
    public function handle(): int
    {
        HitungUlangPengingat::dispatch();
        $this->info('Job HitungUlangPengingat dikirim ke antrean.');

        return self::SUCCESS;
    }
}

<?php

namespace App\Console\Commands;

use App\Actions\Pengingat\KirimPengingatJatuhTempo;
use Illuminate\Console\Command;

class KirimPengingat extends Command
{
    protected $signature = 'sdm:kirim-pengingat';

    protected $description = 'Kirim pengingat jatuh tempo per tahap (database, surel, WhatsApp opsional); idempoten per tahap';

    public function handle(KirimPengingatJatuhTempo $kirim): int
    {
        $hasil = $kirim->handle();

        $this->info("{$hasil['pengingat']} pengingat diproses, {$hasil['notifikasi']} notifikasi dikirim ke antrean.");

        return self::SUCCESS;
    }
}

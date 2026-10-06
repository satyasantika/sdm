<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class BersihkanTmp extends Command
{
    protected $signature = 'sdm:bersihkan-tmp';

    protected $description = 'Hapus berkas keluaran sementara (ekspor/impor) yang lebih tua dari umur_tmp_jam (BR-24)';

    public function handle(): int
    {
        $disk = Storage::disk((string) config('berkas.disk_tmp', 'tmp'));
        $batas = now()->subHours((int) config('berkas.umur_tmp_jam', 24))->getTimestamp();
        $dihapus = 0;

        foreach ($disk->allFiles() as $berkas) {
            if ($disk->lastModified($berkas) < $batas) {
                $disk->delete($berkas);
                $dihapus++;
            }
        }

        $this->info("{$dihapus} berkas sementara dihapus.");

        return self::SUCCESS;
    }
}

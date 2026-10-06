<?php

namespace App\Console\Commands;

use App\Models\Pegawai;
use App\Support\HashIdentitas;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class HitungUlangHash extends Command
{
    protected $signature = 'sdm:hitung-ulang-hash';

    protected $description = 'Hitung ulang nik_hash semua pegawai (termasuk yang dihapus lunak) dari NIK terdekripsi, setelah rotasi APP_KEY';

    public function handle(): int
    {
        $diperbarui = 0;
        $gagal = 0;

        Pegawai::withTrashed()->whereNotNull('nik')->chunkById(200, function (Collection $kelompok) use (&$diperbarui, &$gagal): void {
            foreach ($kelompok as $pegawai) {
                try {
                    $nik = (string) $pegawai->nik; // didekripsi dengan APP_KEY / APP_PREVIOUS_KEYS
                } catch (\Throwable) { // @phpstan-ignore catch.neverThrown
                    $gagal++;
                    $this->error("NIK pegawai {$pegawai->getKey()} tidak dapat didekripsi (periksa APP_KEY dan APP_PREVIOUS_KEYS).");

                    continue;
                }

                // query langsung: tidak memicu observer/log aktivitas dan tidak mengubah updated_at
                DB::table('pegawai')->where('id', $pegawai->getKey())->update(['nik_hash' => HashIdentitas::nik($nik)]);
                $diperbarui++;
            }
        });

        $this->info("nik_hash dihitung ulang untuk {$diperbarui} pegawai.");

        if ($gagal > 0) { // @phpstan-ignore greater.alwaysFalse
            $this->error("{$gagal} pegawai gagal diproses.");

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}

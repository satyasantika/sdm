<?php

namespace App\Console\Commands;

use App\Enums\StatusBerlaku;
use App\Enums\StatusPengingat;
use App\Models\DokumenPegawai;
use App\Models\Pengingat;
use App\Models\Sertifikasi;
use Carbon\CarbonInterface;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class TandaiKedaluwarsa extends Command
{
    protected $signature = 'sdm:tandai-kedaluwarsa';

    protected $description = 'Perbarui status berlaku dokumen & sertifikasi (BR-20) dan tandai pengingat yang lewat tempo';

    public function handle(): int
    {
        $dokumen = $this->perbaruiStatus(DokumenPegawai::query());
        $sertifikasi = $this->perbaruiStatus(Sertifikasi::query());

        $lewat = Pengingat::query()
            ->where('status', StatusPengingat::Aktif->value)
            ->whereDate('tanggal_jatuh_tempo', '<', now()->toDateString())
            ->update(['status' => StatusPengingat::LewatTempo->value]);

        $this->info("Dokumen diperbarui: {$dokumen}; sertifikasi diperbarui: {$sertifikasi}; pengingat lewat tempo: {$lewat}.");

        return self::SUCCESS;
    }

    /** @param  Builder<covariant Model>  $query */
    private function perbaruiStatus($query): int
    {
        $berubah = 0;

        $query->whereNotNull('tanggal_kedaluwarsa')->chunkById(200, function ($kelompok) use (&$berubah): void {
            /** @var Model&object{status_berlaku: StatusBerlaku, tanggal_kedaluwarsa: CarbonInterface} $model */
            foreach ($kelompok as $model) {
                $baru = StatusBerlaku::dariTanggal($model->tanggal_kedaluwarsa);

                if ($baru !== $model->status_berlaku) {
                    $model->forceFill(['status_berlaku' => $baru])->saveQuietly();
                    $berubah++;
                }
            }
        });

        return $berubah;
    }
}

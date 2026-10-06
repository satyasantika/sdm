<?php

namespace App\Actions\Berkas;

use App\Enums\StatusCekTautan;
use App\Jobs\PeriksaTautanBerkas;
use App\Models\TautanBerkas;
use App\Models\User;
use App\Support\DriveUrl;
use Illuminate\Support\Facades\DB;

class GantiTautanBerkas
{
    public function handle(TautanBerkas $tautan, string $urlBaru, ?User $oleh = null, bool $konfirmasiTerbatas = false): TautanBerkas
    {
        $urlBaru = trim($urlBaru);
        SimpanTautanBerkas::validasi($urlBaru, $tautan->jenis, $konfirmasiTerbatas);

        DB::transaction(function () use ($tautan, $urlBaru, $oleh, $konfirmasiTerbatas): void {
            // activitylog mencatat url lama → baru (BR-24).
            $tautan->update([
                'url' => $urlBaru,
                'penyedia' => DriveUrl::penyedia($urlBaru),
                'drive_file_id' => DriveUrl::fileId($urlBaru),
                'status_cek' => StatusCekTautan::Belum,
                'dicek_pada' => null,
                'kode_http_terakhir' => null,
                'ditambahkan_oleh' => $oleh?->getKey() ?? $tautan->ditambahkan_oleh,
            ]);

            if ($konfirmasiTerbatas) {
                SimpanTautanBerkas::catatKonfirmasi($tautan, $oleh);
            }
        });

        PeriksaTautanBerkas::dispatch($tautan->getKey());

        return $tautan;
    }
}

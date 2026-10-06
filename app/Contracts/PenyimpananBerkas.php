<?php

namespace App\Contracts;

use App\Enums\JenisTautan;
use App\Models\Pegawai;
use App\Models\TautanBerkas;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Satu-satunya pintu akses berkas (STANDAR-TEKNIS §1a butir 7). Implementasi aktif: TautanEksternal;
 * kelak dapat ditambah DiskLokal/S3 tanpa mengubah modul.
 */
interface PenyimpananBerkas
{
    public function simpan(
        Model $pemilik,
        JenisTautan $jenis,
        string $url,
        ?string $label = null,
        ?Pegawai $pegawai = null,
        ?User $oleh = null,
        bool $konfirmasiTerbatas = false,
    ): TautanBerkas;

    public function ganti(TautanBerkas $tautan, string $urlBaru, ?User $oleh = null, bool $konfirmasiTerbatas = false): TautanBerkas;

    /** URL route buka berotorisasi (bukan URL berkas mentah). */
    public function urlBuka(TautanBerkas $tautan): string;

    public function urlPratinjau(TautanBerkas $tautan): ?string;

    public function urlGambar(TautanBerkas $tautan): ?string;
}

<?php

namespace App\Berkas;

use App\Actions\Berkas\GantiTautanBerkas;
use App\Actions\Berkas\SimpanTautanBerkas;
use App\Contracts\PenyimpananBerkas;
use App\Enums\JenisTautan;
use App\Models\Pegawai;
use App\Models\TautanBerkas;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class TautanEksternal implements PenyimpananBerkas
{
    public function simpan(
        Model $pemilik,
        JenisTautan $jenis,
        string $url,
        ?string $label = null,
        ?Pegawai $pegawai = null,
        ?User $oleh = null,
        bool $konfirmasiTerbatas = false,
    ): TautanBerkas {
        return app(SimpanTautanBerkas::class)->handle($pemilik, $jenis, $url, $label, $pegawai, $oleh, $konfirmasiTerbatas);
    }

    public function ganti(TautanBerkas $tautan, string $urlBaru, ?User $oleh = null, bool $konfirmasiTerbatas = false): TautanBerkas
    {
        return app(GantiTautanBerkas::class)->handle($tautan, $urlBaru, $oleh, $konfirmasiTerbatas);
    }

    public function urlBuka(TautanBerkas $tautan): string
    {
        return route('tautan.buka', $tautan);
    }

    public function urlPratinjau(TautanBerkas $tautan): ?string
    {
        return $tautan->drive_file_id
            ? 'https://drive.google.com/file/d/'.$tautan->drive_file_id.'/preview'
            : null;
    }

    public function urlGambar(TautanBerkas $tautan): ?string
    {
        return $tautan->drive_file_id
            ? 'https://lh3.googleusercontent.com/d/'.$tautan->drive_file_id
            : null;
    }
}

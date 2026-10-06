<?php

namespace App\Actions\Riwayat;

use App\Models\Pegawai;
use App\Models\RiwayatJabatanFungsional;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class HapusRiwayatJabatanFungsional
{
    /** BR-23: riwayat terkini tidak dihapus tanpa pengganti. */
    public function handle(RiwayatJabatanFungsional $riwayat): void
    {
        DB::transaction(function () use ($riwayat): void {
            $pegawai = Pegawai::query()->lockForUpdate()->findOrFail($riwayat->pegawai_id);

            $lain = RiwayatJabatanFungsional::where('pegawai_id', $pegawai->getKey())->whereKeyNot($riwayat->getKey())->exists();
            if ($riwayat->is_terkini && ! $lain) {
                throw ValidationException::withMessages(['riwayat' => 'Riwayat terkini tidak dapat dihapus tanpa riwayat pengganti.']);
            }

            $riwayat->delete();
            app(SinkronkanJabatanTerkini::class)->handle($pegawai);
        });
    }
}

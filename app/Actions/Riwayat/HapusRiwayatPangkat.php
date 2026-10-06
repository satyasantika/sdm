<?php

namespace App\Actions\Riwayat;

use App\Models\Pegawai;
use App\Models\RiwayatPangkat;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class HapusRiwayatPangkat
{
    /** BR-23: riwayat terkini tidak dihapus tanpa pengganti. */
    public function handle(RiwayatPangkat $riwayat): void
    {
        DB::transaction(function () use ($riwayat): void {
            $pegawai = Pegawai::query()->lockForUpdate()->findOrFail($riwayat->pegawai_id);

            $lain = RiwayatPangkat::where('pegawai_id', $pegawai->getKey())->whereKeyNot($riwayat->getKey())->exists();
            if ($riwayat->is_terkini && ! $lain) {
                throw ValidationException::withMessages(['riwayat' => 'Riwayat terkini tidak dapat dihapus tanpa riwayat pengganti.']);
            }

            $riwayat->delete();
            app(SinkronkanPangkatTerkini::class)->handle($pegawai);
        });
    }
}

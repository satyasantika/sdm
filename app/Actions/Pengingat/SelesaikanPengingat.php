<?php

namespace App\Actions\Pengingat;

use App\Enums\JenisPengingat;
use App\Enums\StatusPengingat;
use App\Models\Pengingat;

/** Menutup pengingat yang masih berjalan untuk jenis tertentu (mis. saat riwayat baru masuk). */
class SelesaikanPengingat
{
    /** @param  list<JenisPengingat>  $jenis */
    public function untukPegawai(string $pegawaiId, array $jenis): int
    {
        return Pengingat::query()
            ->where('pegawai_id', $pegawaiId)
            ->whereIn('jenis', array_map(fn (JenisPengingat $j) => $j->value, $jenis))
            ->whereIn('status', StatusPengingat::nilaiBerjalan())
            ->update(['status' => StatusPengingat::Selesai->value]);
    }
}

<?php

namespace App\Actions\Pegawai;

use App\Models\Pegawai;
use Illuminate\Support\Facades\DB;

class PerbaruiPegawai
{
    /** Kolom tulis-saja: nilai kosong berarti "tidak diubah", bukan menghapus. */
    private const TULIS_SAJA = ['nik', 'npwp', 'nomor_rekening'];

    /** @param  array<string, mixed>  $data */
    public function handle(Pegawai $pegawai, array $data): Pegawai
    {
        foreach (self::TULIS_SAJA as $kolom) {
            if (array_key_exists($kolom, $data) && blank($data[$kolom])) {
                unset($data[$kolom]);
            }
        }

        DB::transaction(fn () => $pegawai->update($data));

        return $pegawai;
    }
}

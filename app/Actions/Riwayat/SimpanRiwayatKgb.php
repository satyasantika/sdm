<?php

namespace App\Actions\Riwayat;

use App\Models\Pegawai;
use App\Models\RiwayatKgb;
use Illuminate\Support\Facades\DB;

class SimpanRiwayatKgb
{
    /** @param  array<string, mixed>  $data */
    public function handle(Pegawai $pegawai, array $data, ?RiwayatKgb $record = null): RiwayatKgb
    {
        return DB::transaction(function () use ($pegawai, $data, $record): RiwayatKgb {
            $pegawai = Pegawai::query()->with('statusKepegawaian')->lockForUpdate()->findOrFail($pegawai->getKey());
            $validasi = app(ValidasiGolonganPegawai::class);
            $validasi->handle($pegawai, filled($data['golongan_id'] ?? null) ? $data['golongan_id'] : null);
            $validasi->masaKerja($data);

            $atribut = [
                'golongan_id' => filled($data['golongan_id'] ?? null) ? $data['golongan_id'] : null,
                'tmt' => $data['tmt'],
                'nomor_sk' => $data['nomor_sk'],
                'tanggal_sk' => $data['tanggal_sk'] ?? null,
                'gaji_pokok' => $data['gaji_pokok'] ?? null,
                'masa_kerja_tahun' => $data['masa_kerja_tahun'] ?? null,
                'masa_kerja_bulan' => $data['masa_kerja_bulan'] ?? null,
            ];

            $record
                ? $record->update($atribut)
                : $record = RiwayatKgb::create($atribut + ['pegawai_id' => $pegawai->getKey()]);

            RiwayatKgb::tandaiTerkini($pegawai->getKey());

            return $record->refresh();
        });
    }
}

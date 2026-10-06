<?php

namespace App\Actions\Riwayat;

use App\Models\Pegawai;
use App\Models\RiwayatPangkat;
use Illuminate\Support\Facades\DB;

class SimpanRiwayatPangkat
{
    /** @param  array<string, mixed>  $data */
    public function handle(Pegawai $pegawai, array $data, ?RiwayatPangkat $record = null): RiwayatPangkat
    {
        return DB::transaction(function () use ($pegawai, $data, $record): RiwayatPangkat {
            $pegawai = Pegawai::query()->with('statusKepegawaian')->lockForUpdate()->findOrFail($pegawai->getKey());
            $validasi = app(ValidasiGolonganPegawai::class);
            $validasi->handle($pegawai, $data['golongan_id'] ?? null);
            $validasi->masaKerja($data);

            $atribut = [
                'golongan_id' => $data['golongan_id'],
                'tmt' => $data['tmt'],
                'nomor_sk' => $data['nomor_sk'],
                'tanggal_sk' => $data['tanggal_sk'] ?? null,
                'jenis_kenaikan' => $data['jenis_kenaikan'],
                'masa_kerja_tahun' => $data['masa_kerja_tahun'] ?? null,
                'masa_kerja_bulan' => $data['masa_kerja_bulan'] ?? null,
            ];

            $record
                ? $record->update($atribut)
                : $record = RiwayatPangkat::create($atribut + ['pegawai_id' => $pegawai->getKey(), 'sumber' => $data['sumber'] ?? 'admin']);

            app(SinkronkanPangkatTerkini::class)->handle($pegawai);

            return $record->refresh();
        });
    }
}

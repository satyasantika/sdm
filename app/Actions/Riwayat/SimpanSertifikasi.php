<?php

namespace App\Actions\Riwayat;

use App\Models\JenisSertifikasi;
use App\Models\Pegawai;
use App\Models\Sertifikasi;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SimpanSertifikasi
{
    /** @param  array<string, mixed>  $data */
    public function handle(Pegawai $pegawai, array $data, ?Sertifikasi $record = null): Sertifikasi
    {
        return DB::transaction(function () use ($pegawai, $data, $record): Sertifikasi {
            Pegawai::query()->lockForUpdate()->findOrFail($pegawai->getKey());
            $jenis = JenisSertifikasi::findOrFail($data['jenis_sertifikasi_id']);

            if ($jenis->punya_masa_berlaku && blank($data['tanggal_kedaluwarsa'] ?? null)) {
                throw ValidationException::withMessages(['tanggal_kedaluwarsa' => "Sertifikat {$jenis->nama} wajib memiliki tanggal kedaluwarsa."]);
            }

            if ($jenis->is_serdos) {
                $sudah = Sertifikasi::query()
                    ->where('pegawai_id', $pegawai->getKey())
                    ->whereHas('jenisSertifikasi', fn ($q) => $q->where('is_serdos', true))
                    ->when($record, fn ($q) => $q->whereKeyNot($record->getKey()))
                    ->exists();

                if ($sudah) {
                    throw ValidationException::withMessages(['jenis_sertifikasi_id' => 'Pegawai sudah memiliki sertifikat pendidik (serdos).']);
                }
            }

            $atribut = collect($data)->only([
                'jenis_sertifikasi_id', 'nama', 'nomor_sertifikat', 'nomor_registrasi', 'bidang', 'penerbit', 'tahun',
                'tanggal_terbit', 'tanggal_kedaluwarsa',
            ])->map(fn ($v) => $v === '' ? null : $v)->all();

            $record
                ? $record->update($atribut)
                : $record = Sertifikasi::create($atribut + ['pegawai_id' => $pegawai->getKey()]);

            return $record->refresh();
        });
    }
}

<?php

namespace App\Actions\Riwayat;

use App\Models\DokumenPegawai;
use App\Models\JenisDokumen;
use App\Models\Pegawai;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SimpanDokumenPegawai
{
    /** @param  array<string, mixed>  $data */
    public function handle(Pegawai $pegawai, array $data, ?User $oleh = null, ?DokumenPegawai $record = null): DokumenPegawai
    {
        $jenis = JenisDokumen::findOrFail($data['jenis_dokumen_id']);

        if ($jenis->is_identitas && $oleh !== null && ! $oleh->hasAnyRole(['super-admin', 'admin-kepegawaian'])) {
            throw ValidationException::withMessages(['jenis_dokumen_id' => 'Dokumen identitas hanya dapat dicatat oleh admin kepegawaian.']);
        }

        if ($jenis->punya_masa_berlaku && blank($data['tanggal_kedaluwarsa'] ?? null)) {
            throw ValidationException::withMessages(['tanggal_kedaluwarsa' => "{$jenis->nama} wajib memiliki tanggal kedaluwarsa."]);
        }

        return DB::transaction(function () use ($pegawai, $data, $record, $jenis): DokumenPegawai {
            Pegawai::query()->lockForUpdate()->findOrFail($pegawai->getKey());

            $atribut = collect($data)->only(['jenis_dokumen_id', 'nomor', 'tanggal_terbit', 'tanggal_kedaluwarsa', 'catatan'])
                ->map(fn ($v) => $v === '' ? null : $v)->all();

            // Nomor identitas hanya disimpan terenkripsi di pegawai.
            if ($jenis->is_identitas) {
                $atribut['nomor'] = null;
            }

            $record
                ? $record->update($atribut)
                : $record = DokumenPegawai::create($atribut + ['pegawai_id' => $pegawai->getKey()]);

            return $record->refresh();
        });
    }
}

<?php

namespace App\Actions\Riwayat;

use App\Models\Pegawai;
use App\Models\Penghargaan;
use Illuminate\Support\Facades\DB;

class SimpanPenghargaan
{
    /** @param  array<string, mixed>  $data */
    public function handle(Pegawai $pegawai, array $data, ?Penghargaan $record = null): Penghargaan
    {
        return DB::transaction(function () use ($pegawai, $data, $record): Penghargaan {
            $atribut = collect($data)->only(['kategori', 'nama', 'pemberi', 'tingkat', 'tanggal', 'nomor_sk'])
                ->map(fn ($v) => $v === '' ? null : $v)->all();
            $atribut['kategori'] ??= 'penghargaan';

            $record
                ? $record->update($atribut)
                : $record = Penghargaan::create($atribut + ['pegawai_id' => $pegawai->getKey()]);

            return $record->refresh();
        });
    }
}

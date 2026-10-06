<?php

namespace App\Actions\Riwayat;

use App\Models\Pegawai;
use App\Models\Pelatihan;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SimpanPelatihan
{
    /** @param  array<string, mixed>  $data */
    public function handle(Pegawai $pegawai, array $data, ?Pelatihan $record = null): Pelatihan
    {
        $mulai = CarbonImmutable::parse($data['tanggal_mulai']);
        $selesai = filled($data['tanggal_selesai'] ?? null) ? CarbonImmutable::parse($data['tanggal_selesai']) : null;
        $jam = filled($data['jumlah_jam'] ?? null) ? (int) $data['jumlah_jam'] : null;

        if ($selesai && $selesai->lessThan($mulai)) {
            throw ValidationException::withMessages(['tanggal_selesai' => 'Tanggal selesai tidak boleh sebelum tanggal mulai.']);
        }

        if ($jam !== null && ($jam < 1 || $jam > 2000)) {
            throw ValidationException::withMessages(['jumlah_jam' => 'Jumlah jam harus antara 1 dan 2000.']);
        }

        return DB::transaction(function () use ($pegawai, $data, $record): Pelatihan {
            $atribut = collect($data)->only(['nama', 'jenis', 'penyelenggara', 'tingkat', 'tanggal_mulai', 'tanggal_selesai', 'jumlah_jam'])
                ->map(fn ($v) => $v === '' ? null : $v)->all();

            $record
                ? $record->update($atribut)
                : $record = Pelatihan::create($atribut + ['pegawai_id' => $pegawai->getKey()]);

            return $record->refresh();
        });
    }
}

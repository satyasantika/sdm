<?php

namespace App\Actions\Riwayat;

use App\Enums\HubunganKeluarga;
use App\Models\Keluarga;
use App\Models\Pegawai;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SimpanKeluarga
{
    /** @param  array<string, mixed>  $data */
    public function handle(Pegawai $pegawai, array $data, ?Keluarga $record = null): Keluarga
    {
        $hubungan = $data['hubungan'] instanceof HubunganKeluarga ? $data['hubungan'] : HubunganKeluarga::from($data['hubungan']);

        $nik = filled($data['nik'] ?? null) ? preg_replace('/\D/', '', (string) $data['nik']) : null;
        if ($nik !== null && strlen($nik) !== 16) {
            throw ValidationException::withMessages(['nik' => 'NIK harus 16 digit angka.']);
        }

        if (filled($data['tanggal_nikah'] ?? null) && ! $hubungan->pasangan()) {
            throw ValidationException::withMessages(['tanggal_nikah' => 'Tanggal nikah hanya untuk suami/istri.']);
        }

        return DB::transaction(function () use ($pegawai, $data, $record, $nik): Keluarga {
            Pegawai::query()->lockForUpdate()->findOrFail($pegawai->getKey());

            $atribut = collect($data)->only([
                'hubungan', 'nama', 'tempat_lahir', 'tanggal_lahir', 'jenis_kelamin', 'pekerjaan', 'status_tunjangan', 'tanggal_nikah',
            ])->map(fn ($v) => $v === '' ? null : $v)->all();

            if ($nik !== null) {
                $atribut['nik'] = $nik;
            }

            $record
                ? $record->update($atribut)
                : $record = Keluarga::create($atribut + ['pegawai_id' => $pegawai->getKey()]);

            return $record->refresh();
        });
    }

    /** Peringatan (bukan penolakan) bila pegawai memiliki lebih dari satu pasangan tercatat. */
    public function adaPasanganGanda(Pegawai $pegawai): bool
    {
        return Keluarga::where('pegawai_id', $pegawai->getKey())->whereIn('hubungan', ['suami', 'istri'])->count() > 1;
    }
}

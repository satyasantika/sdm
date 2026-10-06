<?php

namespace App\Actions\Riwayat;

use App\Models\Golongan;
use App\Models\Pegawai;
use Illuminate\Validation\ValidationException;

/** BR-10: jenis golongan harus sesuai status kepegawaian; Non-ASN tidak punya riwayat pangkat ASN. */
class ValidasiGolonganPegawai
{
    public function handle(Pegawai $pegawai, ?string $golonganId): ?Golongan
    {
        $jenis = $pegawai->statusKepegawaian->jenis_golongan;

        if ($jenis === null) {
            throw ValidationException::withMessages(['golongan_id' => 'Pegawai Non-ASN tidak memiliki riwayat pangkat ASN.']);
        }

        if ($golonganId === null) {
            return null;
        }

        $golongan = Golongan::findOrFail($golonganId);
        if ($golongan->jenis !== $jenis) {
            throw ValidationException::withMessages([
                'golongan_id' => "Golongan {$golongan->jenis->getLabel()} tidak sesuai dengan status kepegawaian ({$jenis->getLabel()}).",
            ]);
        }

        return $golongan;
    }

    /** @param  array<string, mixed>  $data */
    public function masaKerja(array $data): void
    {
        $bulan = $data['masa_kerja_bulan'] ?? null;
        $tahun = $data['masa_kerja_tahun'] ?? null;

        if ($bulan !== null && $bulan !== '' && ((int) $bulan < 0 || (int) $bulan > 11)) {
            throw ValidationException::withMessages(['masa_kerja_bulan' => 'Masa kerja bulan harus 0–11.']);
        }

        if ($tahun !== null && $tahun !== '' && ((int) $tahun < 0 || (int) $tahun > 60)) {
            throw ValidationException::withMessages(['masa_kerja_tahun' => 'Masa kerja tahun harus 0–60.']);
        }
    }
}

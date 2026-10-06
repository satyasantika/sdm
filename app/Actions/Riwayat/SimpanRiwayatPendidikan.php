<?php

namespace App\Actions\Riwayat;

use App\Models\Pegawai;
use App\Models\RiwayatPendidikan;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SimpanRiwayatPendidikan
{
    /** @param  array<string, mixed>  $data */
    public function handle(Pegawai $pegawai, array $data, ?RiwayatPendidikan $record = null): RiwayatPendidikan
    {
        $masuk = filled($data['tahun_masuk'] ?? null) ? (int) $data['tahun_masuk'] : null;
        $lulus = filled($data['tahun_lulus'] ?? null) ? (int) $data['tahun_lulus'] : null;
        $ipk = filled($data['ipk'] ?? null) ? (float) $data['ipk'] : null;

        if ($masuk !== null && $lulus !== null && $lulus < $masuk) {
            throw ValidationException::withMessages(['tahun_lulus' => 'Tahun lulus tidak boleh sebelum tahun masuk.']);
        }

        if ($lulus !== null && $lulus > (int) now()->year) {
            throw ValidationException::withMessages(['tahun_lulus' => 'Tahun lulus tidak boleh melebihi tahun sekarang.']);
        }

        if ($ipk !== null && ($ipk < 0 || $ipk > 4)) {
            throw ValidationException::withMessages(['ipk' => 'IPK harus antara 0 dan 4.']);
        }

        return DB::transaction(function () use ($pegawai, $data, $record): RiwayatPendidikan {
            Pegawai::query()->lockForUpdate()->findOrFail($pegawai->getKey());

            $atribut = collect($data)->only([
                'jenjang_pendidikan_id', 'nama_pt', 'negara', 'nama_prodi', 'bidang_ilmu', 'gelar', 'tahun_masuk',
                'tahun_lulus', 'nomor_ijazah', 'ipk', 'judul_tugas_akhir',
            ])->map(fn ($v) => $v === '' ? null : $v)->all();
            $atribut['negara'] ??= 'Indonesia';

            $record
                ? $record->update($atribut)
                : $record = RiwayatPendidikan::create($atribut + ['pegawai_id' => $pegawai->getKey()]);

            app(TandaiPendidikanTertinggi::class)->handle($pegawai);

            return $record->refresh();
        });
    }

    public function hapus(RiwayatPendidikan $riwayat): void
    {
        DB::transaction(function () use ($riwayat): void {
            $pegawai = Pegawai::query()->lockForUpdate()->findOrFail($riwayat->pegawai_id);
            $riwayat->delete();
            app(TandaiPendidikanTertinggi::class)->handle($pegawai);
        });
    }
}

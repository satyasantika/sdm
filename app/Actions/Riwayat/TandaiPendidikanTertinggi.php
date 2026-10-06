<?php

namespace App\Actions\Riwayat;

use App\Events\DataPegawaiBerubah;
use App\Models\Pegawai;
use App\Models\RiwayatPendidikan;

/** Menandai riwayat dengan jenjang tertinggi (bila sama, tahun lulus terbaru) sebagai pendidikan tertinggi. */
class TandaiPendidikanTertinggi
{
    public function handle(Pegawai $pegawai): ?RiwayatPendidikan
    {
        RiwayatPendidikan::where('pegawai_id', $pegawai->getKey())->where('is_pendidikan_tertinggi', true)->update(['is_pendidikan_tertinggi' => false]);

        /** @var RiwayatPendidikan|null $tertinggi */
        $tertinggi = RiwayatPendidikan::query()
            ->where('pegawai_id', $pegawai->getKey())
            ->with('jenjangPendidikan')
            ->get()
            ->sortByDesc(fn (RiwayatPendidikan $r): array => [$r->jenjangPendidikan->urutan, $r->tahun_lulus ?? 0])
            ->first();

        $tertinggi?->forceFill(['is_pendidikan_tertinggi' => true])->saveQuietly();

        // saveQuietly() tidak memicu pembersihan cache dasbor, padahal penanda inilah yang dibaca statistik jenjang tertinggi.
        DataPegawaiBerubah::dispatch($pegawai->prodi_id);

        return $tertinggi;
    }
}

<?php

namespace App\Actions\Laporan;

use App\Enums\JenisPegawai;
use App\Enums\StatusAktifPegawai;
use App\Models\JumlahMahasiswaProdi;
use App\Models\Pegawai;
use App\Models\Prodi;
use App\Models\Semester;
use App\Support\Konfigurasi;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * LAP-02: rasio dosen–mahasiswa per prodi (BR-25, BR-26). Dosen tetap = scope dosenTetap();
 * "aktif mengajar" = dosen tetap tanpa tugas belajar. Ambang opsional dari konfigurasi (tidak di-hard-code).
 */
class HitungRasioDosenMahasiswa
{
    public const TTL = 21600;

    /** @return Collection<int, array<string, mixed>> */
    public function handle(Semester $semester): Collection
    {
        /** @var array<int, array<string, mixed>> $baris */
        $baris = Cache::remember('sdm:dasbor:rasio:'.$semester->getKey(), self::TTL, fn (): array => $this->hitung($semester)->all());

        return collect($baris);
    }

    /** @return Collection<int, array<string, mixed>> */
    private function hitung(Semester $semester): Collection
    {
        $ambang = Konfigurasi::get('ambang_rasio_dosen_mahasiswa');
        $ambang = is_numeric($ambang) && (float) $ambang > 0 ? (float) $ambang : null;

        $mahasiswa = JumlahMahasiswaProdi::query()->where('semester_id', $semester->getKey())->pluck('jumlah_mahasiswa_aktif', 'prodi_id');

        $tetap = Pegawai::query()->dosenTetap()->selectRaw('prodi_id, count(*) as jumlah')->groupBy('prodi_id')->pluck('jumlah', 'prodi_id');
        $mengajar = Pegawai::query()->dosenTetap()->where('status_aktif', StatusAktifPegawai::Aktif->value)
            ->where('jenis_pegawai', JenisPegawai::Dosen->value)->selectRaw('prodi_id, count(*) as jumlah')->groupBy('prodi_id')->pluck('jumlah', 'prodi_id');

        /** @var Collection<int, array<string, mixed>> $hasil */
        $hasil = Prodi::query()->where('is_aktif', true)->orderBy('nama')->get()->map(function (Prodi $prodi) use ($mahasiswa, $tetap, $mengajar, $ambang): array {
            $dosen = (int) ($tetap[$prodi->id] ?? 0);
            $jumlahMahasiswa = $mahasiswa[$prodi->id] ?? null;
            $nilai = ($jumlahMahasiswa !== null && $dosen > 0) ? round($jumlahMahasiswa / $dosen, 1) : null;

            return [
                'prodi_id' => (string) $prodi->id,
                'prodi' => $prodi->nama,
                'jumlah_dosen_tetap' => $dosen,
                'dosen_tetap_aktif_mengajar' => (int) ($mengajar[$prodi->id] ?? 0),
                'jumlah_mahasiswa_aktif' => $jumlahMahasiswa,
                'rasio' => $nilai === null ? '—' : '1 : '.number_format($nilai, 1, ',', '.'),
                'rasio_nilai' => $nilai,
                'melampaui' => $ambang !== null && $nilai !== null && $nilai > $ambang,
            ];
        })->values();

        return $hasil;
    }
}

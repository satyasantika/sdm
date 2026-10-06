<?php

namespace App\Actions\Laporan;

use App\Enums\JenisPegawai;
use App\Enums\StatusAktifPegawai;
use App\Models\Pegawai;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

/** Statistik dasbor (kueri agregat, tanpa N+1), di-cache 1 jam pada sdm:dasbor:statistik:{fakultas|prodi:id}. */
class HitungStatistikDasbor
{
    public const TTL = 3600;

    public static function kunci(?string $prodiId): string
    {
        return 'sdm:dasbor:statistik:'.($prodiId ? "prodi:{$prodiId}" : 'fakultas');
    }

    /** @return array<string, mixed> */
    public function handle(?string $prodiId = null): array
    {
        return Cache::remember(self::kunci($prodiId), self::TTL, fn (): array => $this->hitung($prodiId));
    }

    /** @return array<string, mixed> */
    private function hitung(?string $prodiId): array
    {
        $aktif = fn (JenisPegawai $jenis): Builder => Pegawai::query()
            ->where('jenis_pegawai', $jenis->value)
            ->where('status_aktif', StatusAktifPegawai::Aktif->value)
            ->when($prodiId, fn (Builder $q) => $q->where('prodi_id', $prodiId));

        $jumlahDosen = $aktif(JenisPegawai::Dosen)->count();
        $jumlahTendik = Pegawai::query()->where('jenis_pegawai', JenisPegawai::Tendik->value)
            ->where('status_aktif', StatusAktifPegawai::Aktif->value)
            ->when($prodiId, fn (Builder $q) => $q->whereHas('unitKerja', fn (Builder $u) => $u->where('prodi_id', $prodiId)))->count();

        $perStatus = $aktif(JenisPegawai::Dosen)->join('status_kepegawaian', 'status_kepegawaian.id', '=', 'pegawai.status_kepegawaian_id')
            ->selectRaw('status_kepegawaian.nama as nama, count(*) as jumlah')->groupBy('status_kepegawaian.nama')
            ->pluck('jumlah', 'nama')->map(fn ($v): int => (int) $v)->all();

        $perJabatan = $aktif(JenisPegawai::Dosen)->leftJoin('jabatan_fungsional', 'jabatan_fungsional.id', '=', 'pegawai.jabatan_fungsional_id')
            ->selectRaw("coalesce(jabatan_fungsional.nama, 'Belum ada') as nama, count(*) as jumlah")->groupBy('jabatan_fungsional.nama')
            ->pluck('jumlah', 'nama')->map(fn ($v): int => (int) $v)->all();

        $perPendidikan = $aktif(JenisPegawai::Dosen)
            ->leftJoin('riwayat_pendidikan', function ($join): void {
                $join->on('riwayat_pendidikan.pegawai_id', '=', 'pegawai.id')->where('riwayat_pendidikan.is_pendidikan_tertinggi', true)->whereNull('riwayat_pendidikan.deleted_at');
            })
            ->leftJoin('jenjang_pendidikan', 'jenjang_pendidikan.id', '=', 'riwayat_pendidikan.jenjang_pendidikan_id')
            ->selectRaw("coalesce(jenjang_pendidikan.kode, 'Belum ada') as kode, count(*) as jumlah")->groupBy('jenjang_pendidikan.kode')
            ->pluck('jumlah', 'kode')->map(fn ($v): int => (int) $v)->all();

        $serdos = $aktif(JenisPegawai::Dosen)->whereExists(function ($q): void {
            $q->selectRaw('1')->from('sertifikasi')->join('jenis_sertifikasi', 'jenis_sertifikasi.id', '=', 'sertifikasi.jenis_sertifikasi_id')
                ->whereColumn('sertifikasi.pegawai_id', 'pegawai.id')->where('jenis_sertifikasi.is_serdos', true)->whereNull('sertifikasi.deleted_at');
        })->count();

        $perProdi = $aktif(JenisPegawai::Dosen)->join('prodi', 'prodi.id', '=', 'pegawai.prodi_id')
            ->selectRaw('prodi.nama as nama, count(*) as jumlah')->groupBy('prodi.nama')->pluck('jumlah', 'nama')->map(fn ($v): int => (int) $v)->all();

        $tugasBelajar = Pegawai::query()->where('status_aktif', StatusAktifPegawai::TugasBelajar->value)
            ->when($prodiId, fn (Builder $q) => $q->where('prodi_id', $prodiId))->count();

        $pensiun = [];
        foreach (Pegawai::query()->whereIn('status_aktif', [StatusAktifPegawai::Aktif->value, StatusAktifPegawai::TugasBelajar->value])
            ->whereBetween('tanggal_pensiun', [now()->toDateString(), now()->addYears(5)->toDateString()])
            ->when($prodiId, fn (Builder $q) => $q->where('prodi_id', $prodiId))->pluck('tanggal_pensiun') as $tanggal) {
            $tahun = (int) substr((string) $tanggal, 0, 4);
            $pensiun[$tahun] = ($pensiun[$tahun] ?? 0) + 1;
        }
        ksort($pensiun);

        $persen = fn (int $bagian): float => $jumlahDosen > 0 ? round($bagian / $jumlahDosen * 100, 1) : 0.0;

        return [
            'jumlah_dosen' => $jumlahDosen,
            'jumlah_tendik' => $jumlahTendik,
            'dosen_per_status' => $perStatus,
            'dosen_per_jabatan' => $perJabatan,
            'dosen_per_pendidikan' => $perPendidikan,
            'persen_s3' => $persen((int) ($perPendidikan['S3'] ?? 0)),
            'jumlah_serdos' => $serdos,
            'persen_serdos' => $persen($serdos),
            'dosen_per_prodi' => $perProdi,
            'tugas_belajar' => $tugasBelajar,
            'pensiun_per_tahun' => $pensiun,
            'dihitung_pada' => now()->toIso8601String(),
        ];
    }
}

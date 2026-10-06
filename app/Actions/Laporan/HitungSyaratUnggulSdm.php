<?php

namespace App\Actions\Laporan;

use App\Models\JabatanFungsional;
use App\Models\Pegawai;
use App\Models\Prodi;
use App\Support\Konfigurasi;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

/**
 * LAP-10: indikator syarat unggul SDM (BR-32) dari konfigurasi `syarat_unggul_sdm` per jenjang.
 * DTPS = dosen tetap yang bidang keahliannya sesuai kompetensi inti prodi homebase (BR-31, perlu verifikasi).
 */
class HitungSyaratUnggulSdm
{
    public const TTL = 3600;

    /**
     * @return array{jenjang: string, dtps: int, doktor: int, lektor_ke_atas: int, lektor_kepala_ke_atas: int, horizon: array<string, array{terpenuhi: bool, syarat: array<string, int>}>}|null
     */
    public function handle(Prodi $prodi): ?array
    {
        return Cache::remember('sdm:statistik:syarat-unggul:'.$prodi->getKey(), self::TTL, fn (): ?array => $this->hitung($prodi));
    }

    /** @return array{jenjang: string, dtps: int, doktor: int, lektor_ke_atas: int, lektor_kepala_ke_atas: int, horizon: array<string, array{terpenuhi: bool, syarat: array<string, int>}>}|null */
    private function hitung(Prodi $prodi): ?array
    {
        $konfigurasi = (array) Konfigurasi::get('syarat_unggul_sdm', []);
        $syarat = $konfigurasi[$prodi->jenjang] ?? null;

        if (! is_array($syarat)) {
            return null;
        }

        $dtps = fn (): Builder => Pegawai::query()->dosenTetap()->where('prodi_id', $prodi->getKey())->where('sesuai_kompetensi_inti_ps', true);

        $urutanLektor = JabatanFungsional::query()->where('kode', 'lektor')->value('urutan');
        $urutanKepala = JabatanFungsional::query()->where('kode', 'lektor-kepala')->value('urutan');

        $minJabatan = fn (?int $urutan): int => $urutan === null ? 0 : $dtps()
            ->whereHas('jabatanFungsional', fn (Builder $q) => $q->where('kelompok', 'dosen')->where('urutan', '>=', $urutan))->count();

        $doktor = $dtps()->whereHas('pendidikanTertinggi.jenjangPendidikan', fn (Builder $q) => $q->where('kode', 'S3'))->count();
        $lektor = $minJabatan($urutanLektor);
        $kepala = $minJabatan($urutanKepala);

        $nilai = ['min_dtps_doktor' => $doktor, 'min_dtps_lektor_ke_atas' => $lektor, 'min_dtps_lektor_kepala_ke_atas' => $kepala];

        $horizon = [];
        foreach ($syarat as $nama => $butuh) {
            $butuh = array_map('intval', (array) $butuh);
            $horizon[(string) $nama] = [
                'syarat' => $butuh,
                'terpenuhi' => collect($butuh)->every(fn (int $min, string $kunci): bool => ($nilai[$kunci] ?? 0) >= $min),
            ];
        }

        return [
            'jenjang' => $prodi->jenjang,
            'dtps' => $dtps()->count(),
            'doktor' => $doktor,
            'lektor_ke_atas' => $lektor,
            'lektor_kepala_ke_atas' => $kepala,
            'horizon' => $horizon,
        ];
    }
}

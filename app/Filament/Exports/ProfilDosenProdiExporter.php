<?php

namespace App\Filament\Exports;

use App\Enums\StatusBerlaku;
use App\Filament\Exports\Concerns\MencatatEkspor;
use App\Models\Pegawai;
use App\Models\RiwayatPendidikan;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Database\Eloquent\Builder;

/**
 * LAP-01: profil dosen tetap per prodi. Tanpa NIK, NIP lengkap, tanggal lahir, alamat, rekening (BR-17).
 * Urutan kolom perlu diverifikasi terhadap templat DKPS resmi (lihat docs/FORMAT-EKSPOR-AKREDITASI.md).
 */
class ProfilDosenProdiExporter extends Exporter
{
    use MencatatEkspor;

    protected static function deskripsiLog(): string
    {
        return 'ekspor profil dosen';
    }

    protected static ?string $model = Pegawai::class;

    public static function getColumns(): array
    {
        $jenjang = function (string $kode, string $bagian) {
            return fn (Pegawai $p): ?string => self::pendidikan($p, $kode, $bagian);
        };

        return [
            ExportColumn::make('nama')->label('Nama dosen')->state(fn (Pegawai $p): string => $p->nama_bergelar),
            ExportColumn::make('nidn')->label('NIDN/NIDK')->state(fn (Pegawai $p): ?string => $p->nidn ?? $p->nidk),
            ExportColumn::make('nuptk')->label('NUPTK'),
            ExportColumn::make('prodi')->label('Prodi homebase')->state(fn (Pegawai $p): ?string => $p->prodi?->nama),
            ExportColumn::make('status_kepegawaian')->label('Status kepegawaian')->state(fn (Pegawai $p): string => $p->statusKepegawaian->nama),
            ExportColumn::make('s1_pt')->label('S1 - Perguruan tinggi')->state($jenjang('S1', 'pt')),
            ExportColumn::make('s1_bidang')->label('S1 - Bidang ilmu')->state($jenjang('S1', 'bidang')),
            ExportColumn::make('s2_pt')->label('S2 - Perguruan tinggi')->state($jenjang('S2', 'pt')),
            ExportColumn::make('s2_bidang')->label('S2 - Bidang ilmu')->state($jenjang('S2', 'bidang')),
            ExportColumn::make('s3_pt')->label('S3 - Perguruan tinggi')->state($jenjang('S3', 'pt')),
            ExportColumn::make('s3_bidang')->label('S3 - Bidang ilmu')->state($jenjang('S3', 'bidang')),
            ExportColumn::make('bidang_keahlian')->label('Bidang keahlian')
                ->state(fn (Pegawai $p): ?string => $p->pendidikanTertinggi?->bidang_ilmu),
            ExportColumn::make('jabatan_akademik')->label('Jabatan akademik')->state(fn (Pegawai $p): ?string => $p->jabatanFungsional?->nama),
            ExportColumn::make('tmt_jabatan')->label('TMT jabatan akademik')
                ->state(fn (Pegawai $p): ?string => $p->jabatanFungsionalTerkini?->tmt?->format('d F Y')),
            ExportColumn::make('serdos')->label('Sertifikat pendidik')->state(function (Pegawai $p): string {
                $serdos = $p->sertifikasi->first(fn ($s) => $s->jenisSertifikasi->is_serdos);

                return $serdos ? 'Ya'.($serdos->nomor_registrasi ? " ({$serdos->nomor_registrasi})" : '') : 'Tidak';
            }),
            ExportColumn::make('sertifikat_kompetensi')->label('Sertifikat kompetensi/profesi')->state(fn (Pegawai $p): string => $p->sertifikasi
                ->filter(fn ($s) => ! $s->jenisSertifikasi->is_serdos && $s->status_berlaku !== StatusBerlaku::Kedaluwarsa)
                ->pluck('nama')->implode('; ')),
            ExportColumn::make('status')->label('Status')->state(fn (Pegawai $p): string => $p->status_aktif->getLabel()),
            ExportColumn::make('dtps')->label('DTPS')->state(fn (Pegawai $p): string => $p->sesuai_kompetensi_inti_ps ? 'Ya' : 'Tidak'),
        ];
    }

    private static function pendidikan(Pegawai $pegawai, string $kode, string $bagian): ?string
    {
        /** @var RiwayatPendidikan|null $r */
        $r = $pegawai->riwayatPendidikan->first(fn (RiwayatPendidikan $r): bool => $r->jenjangPendidikan->kode === $kode);

        return $r === null ? null : ($bagian === 'pt' ? $r->nama_pt : $r->bidang_ilmu);
    }

    public static function modifyQuery(Builder $query): Builder
    {
        /** @var Builder<Pegawai> $query */
        $hasil = $query->dosenTetap()->with([
            'prodi', 'statusKepegawaian', 'jabatanFungsional', 'jabatanFungsionalTerkini', 'pendidikanTertinggi',
            'riwayatPendidikan.jenjangPendidikan', 'sertifikasi.jenisSertifikasi',
        ]);

        return $hasil; // @phpstan-ignore return.type
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        return 'Ekspor profil dosen tetap selesai: '.number_format($export->successful_rows).' dosen. Berkas dihapus otomatis dalam 24 jam.';
    }

    public function getJobQueue(): ?string
    {
        return 'ekspor';
    }
}

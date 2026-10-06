<?php

namespace App\Filament\Exports;

use App\Actions\Laporan\HitungRasioDosenMahasiswa;
use App\Models\Prodi;
use App\Models\Semester;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Filament\Forms\Components\Select;

/** LAP-02: rasio dosen–mahasiswa per prodi untuk semester terpilih. */
class RasioDosenMahasiswaExporter extends Exporter
{
    protected static ?string $model = Prodi::class;

    /**
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    private static function baris(Prodi $prodi, array $options): array
    {
        $semester = Semester::find($options['semester_id'] ?? null);

        return $semester
            ? (app(HitungRasioDosenMahasiswa::class)->handle($semester)->firstWhere('prodi_id', (string) $prodi->id) ?? [])
            : [];
    }

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('prodi')->label('Program studi')->state(fn (Prodi $p): string => $p->nama),
            ExportColumn::make('dosen_tetap')->label('Dosen tetap')->state(fn (Prodi $p, array $options) => self::baris($p, $options)['jumlah_dosen_tetap'] ?? null),
            ExportColumn::make('aktif_mengajar')->label('Dosen tetap aktif mengajar')->state(fn (Prodi $p, array $options) => self::baris($p, $options)['dosen_tetap_aktif_mengajar'] ?? null),
            ExportColumn::make('mahasiswa')->label('Mahasiswa aktif')->state(fn (Prodi $p, array $options) => self::baris($p, $options)['jumlah_mahasiswa_aktif'] ?? null),
            ExportColumn::make('rasio')->label('Rasio')->state(fn (Prodi $p, array $options) => self::baris($p, $options)['rasio'] ?? '—'),
        ];
    }

    public static function getOptionsFormComponents(): array
    {
        return [
            Select::make('semester_id')->label('Semester')->required()
                ->options(fn (): array => Semester::query()->orderByDesc('kode')->get()->mapWithKeys(fn (Semester $s): array => [$s->id => $s->label])->all())
                ->default(fn (): ?string => Semester::aktifSekarang()?->id),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        return 'Ekspor rasio dosen–mahasiswa selesai: '.number_format($export->successful_rows).' prodi.';
    }

    public function getJobQueue(): ?string
    {
        return 'ekspor';
    }
}

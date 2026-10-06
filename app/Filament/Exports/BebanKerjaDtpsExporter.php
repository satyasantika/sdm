<?php

namespace App\Filament\Exports;

use App\Models\Bkd;
use App\Models\Semester;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Filament\Forms\Components\Select;
use Illuminate\Database\Eloquent\Builder;

/** DKPS: Beban Kerja DTPS (dari rekap BKD semester terpilih). */
class BebanKerjaDtpsExporter extends Exporter
{
    protected static ?string $model = Bkd::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('nama')->label('Nama DTPS')->state(fn (Bkd $r): string => $r->pegawai->nama_bergelar),
            ExportColumn::make('nidn')->label('NIDN')->state(fn (Bkd $r): ?string => $r->pegawai->nidn),
            ExportColumn::make('semester')->label('Semester')->state(fn (Bkd $r): string => $r->semester->label),
            ExportColumn::make('sks_pendidikan')->label('SKS pendidikan'),
            ExportColumn::make('sks_penelitian')->label('SKS penelitian'),
            ExportColumn::make('sks_pengabdian')->label('SKS pengabdian'),
            ExportColumn::make('sks_penunjang')->label('SKS penunjang'),
            ExportColumn::make('total_sks')->label('Total SKS'),
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

    public static function modifyQuery(Builder $query): Builder
    {
        return $query->with(['pegawai', 'semester']);
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        return 'Ekspor beban kerja DTPS selesai: '.number_format($export->successful_rows).' baris.';
    }

    public function getJobQueue(): ?string
    {
        return 'ekspor';
    }
}

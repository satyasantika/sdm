<?php

namespace App\Filament\Exports;

use App\Models\Pegawai;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Database\Eloquent\Builder;

/** DKPS: Tenaga Kependidikan per unit, dengan pendidikan tertinggi. */
class TenagaKependidikanExporter extends Exporter
{
    protected static ?string $model = Pegawai::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('nama')->label('Nama tenaga kependidikan')->state(fn (Pegawai $p): string => $p->nama_bergelar),
            ExportColumn::make('unit')->label('Unit kerja')->state(fn (Pegawai $p): ?string => $p->unitKerja?->nama),
            ExportColumn::make('status_kepegawaian')->label('Status kepegawaian')->state(fn (Pegawai $p): string => $p->statusKepegawaian->nama),
            ExportColumn::make('jabatan')->label('Jabatan fungsional')->state(fn (Pegawai $p): ?string => $p->jabatanFungsional?->nama),
            ExportColumn::make('pendidikan')->label('Pendidikan tertinggi')->state(fn (Pegawai $p): ?string => $p->pendidikanTertinggi?->jenjangPendidikan->nama),
            ExportColumn::make('status')->label('Status')->state(fn (Pegawai $p): string => $p->status_aktif->getLabel()),
        ];
    }

    public static function modifyQuery(Builder $query): Builder
    {
        /** @var Builder<Pegawai> $query */
        $hasil = $query->tendik()->aktif()->with(['unitKerja', 'statusKepegawaian', 'jabatanFungsional', 'pendidikanTertinggi.jenjangPendidikan']);

        return $hasil; // @phpstan-ignore return.type
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        return 'Ekspor tenaga kependidikan selesai: '.number_format($export->successful_rows).' baris.';
    }

    public function getJobQueue(): ?string
    {
        return 'ekspor';
    }
}

<?php

namespace App\Filament\Exports;

use App\Models\Penghargaan;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Database\Eloquent\Builder;

/** DKPS: Rekognisi Kepakaran/Prestasi DTPS (pemetaan kategori perlu verifikasi). */
class RekognisiDtpsExporter extends Exporter
{
    protected static ?string $model = Penghargaan::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('nama_dosen')->label('Nama DTPS')->state(fn (Penghargaan $r): string => $r->pegawai->nama_bergelar),
            ExportColumn::make('kategori')->label('Kategori')->state(fn (Penghargaan $r): string => $r->kategori->getLabel()),
            ExportColumn::make('nama')->label('Rekognisi/prestasi'),
            ExportColumn::make('pemberi')->label('Pemberi'),
            ExportColumn::make('tingkat')->label('Tingkat')->state(fn (Penghargaan $r): string => $r->tingkat->getLabel()),
            ExportColumn::make('tanggal')->label('Tanggal')->state(fn (Penghargaan $r): ?string => $r->tanggal?->format('d F Y')),
        ];
    }

    public static function modifyQuery(Builder $query): Builder
    {
        return $query->with('pegawai');
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        return 'Ekspor rekognisi DTPS selesai: '.number_format($export->successful_rows).' baris.';
    }

    public function getJobQueue(): ?string
    {
        return 'ekspor';
    }
}

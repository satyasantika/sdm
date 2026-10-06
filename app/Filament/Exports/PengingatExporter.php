<?php

namespace App\Filament\Exports;

use App\Models\Pengingat;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Database\Eloquent\Builder;

/** LAP-06: daftar jatuh tempo tanpa data sensitif (BR-17). */
class PengingatExporter extends Exporter
{
    protected static ?string $model = Pengingat::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('nama')->label('Nama')->state(fn (Pengingat $r): string => $r->pegawai->nama_bergelar),
            ExportColumn::make('prodi')->label('Prodi')->state(fn (Pengingat $r): ?string => $r->pegawai->prodi?->nama),
            ExportColumn::make('jenis')->label('Jenis')->state(fn (Pengingat $r): string => $r->jenis->getLabel()),
            ExportColumn::make('tanggal_jatuh_tempo')->label('Jatuh tempo')->state(fn (Pengingat $r): string => $r->tanggal_jatuh_tempo->format('d F Y')),
            ExportColumn::make('sisa')->label('Sisa hari')->state(fn (Pengingat $r): string => $r->sisaHariLabel()),
            ExportColumn::make('status')->label('Status')->state(fn (Pengingat $r): string => $r->status->getLabel()),
        ];
    }

    public static function modifyQuery(Builder $query): Builder
    {
        return $query->with(['pegawai.prodi']);
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        return 'Ekspor daftar jatuh tempo selesai: '.number_format($export->successful_rows).' baris. Berkas dihapus otomatis dalam 24 jam.';
    }

    public function getJobQueue(): ?string
    {
        return 'ekspor';
    }
}

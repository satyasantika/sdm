<?php

namespace App\Filament\Exports;

use App\Models\Bkd;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

/** Rekap BKD tanpa data sensitif (BR-17): tidak memuat NIK, NPWP, rekening, alamat, tanggal lahir, atau keluarga. */
class RekapBkdExporter extends Exporter
{
    protected static ?string $model = Bkd::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('nama')->label('Nama')->state(fn (Bkd $r): string => $r->pegawai->nama_bergelar),
            ExportColumn::make('nidn')->label('NIDN')->state(fn (Bkd $r): ?string => $r->pegawai->nidn),
            ExportColumn::make('prodi')->label('Prodi')->state(fn (Bkd $r): ?string => $r->pegawai->prodi?->nama),
            ExportColumn::make('jabatan_fungsional')->label('Jabatan fungsional')->state(fn (Bkd $r): ?string => $r->pegawai->jabatanFungsional?->nama),
            ExportColumn::make('semester')->label('Semester')->state(fn (Bkd $r): string => $r->semester->label),
            ExportColumn::make('sks_pendidikan')->label('SKS pendidikan'),
            ExportColumn::make('sks_penelitian')->label('SKS penelitian'),
            ExportColumn::make('sks_pengabdian')->label('SKS pengabdian'),
            ExportColumn::make('sks_penunjang')->label('SKS penunjang'),
            ExportColumn::make('total_sks')->label('Total SKS'),
            ExportColumn::make('kesimpulan')->label('Kesimpulan')->state(fn (Bkd $r): string => $r->kesimpulan->getLabel()),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        return 'Ekspor rekap BKD selesai: '.number_format($export->successful_rows).' baris. Berkas dihapus otomatis dalam 24 jam.';
    }

    public function getJobQueue(): ?string
    {
        return 'ekspor';
    }
}

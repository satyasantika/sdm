<?php

namespace App\Filament\Exports;

use App\Models\Pelatihan;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Filament\Forms\Components\Select;
use Illuminate\Database\Eloquent\Builder;

/** DKPS: Pengembangan Kompetensi DTPS / Tenaga Kependidikan (opsi jenis pegawai). */
class PengembanganKompetensiExporter extends Exporter
{
    protected static ?string $model = Pelatihan::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('nama_pegawai')->label('Nama')->state(fn (Pelatihan $r): string => $r->pegawai->nama_bergelar),
            ExportColumn::make('jenis_pegawai')->label('Jenis pegawai')->state(fn (Pelatihan $r): string => $r->pegawai->jenis_pegawai->getLabel()),
            ExportColumn::make('nama')->label('Pelatihan'),
            ExportColumn::make('jenis')->label('Jenis')->state(fn (Pelatihan $r): string => $r->jenis->getLabel()),
            ExportColumn::make('penyelenggara')->label('Penyelenggara'),
            ExportColumn::make('tanggal_mulai')->label('Mulai')->state(fn (Pelatihan $r): string => $r->tanggal_mulai->format('d F Y')),
            ExportColumn::make('jumlah_jam')->label('Jumlah jam'),
        ];
    }

    public static function getOptionsFormComponents(): array
    {
        return [
            Select::make('jenis_pegawai')->label('Jenis pegawai')->options(['dosen' => 'Dosen', 'tendik' => 'Tenaga kependidikan'])->default('dosen')->required(),
        ];
    }

    public static function modifyQuery(Builder $query): Builder
    {
        return $query->with('pegawai');
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        return 'Ekspor pengembangan kompetensi selesai: '.number_format($export->successful_rows).' baris.';
    }

    public function getJobQueue(): ?string
    {
        return 'ekspor';
    }
}

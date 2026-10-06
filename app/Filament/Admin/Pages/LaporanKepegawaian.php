<?php

namespace App\Filament\Admin\Pages;

use App\Actions\Laporan\SusunLaporanKepegawaian;
use App\Jobs\BuatPdfLaporan;
use App\Support\BatasEkspor;
use App\Support\TulisXlsx;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class LaporanKepegawaian extends Page
{
    protected string $view = 'filament.admin.pages.laporan-kepegawaian';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string|UnitEnum|null $navigationGroup = 'Laporan';

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationLabel = 'Laporan Kepegawaian';

    protected static ?string $title = 'Laporan Kepegawaian';

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->can('laporan.lihat');
    }

    private function boleh(): bool
    {
        return (bool) auth()->user()?->can('laporan.ekspor');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('dukPdf')->label('DUK (PDF)')->icon(Heroicon::OutlinedDocumentArrowDown)
                ->visible(fn (): bool => $this->boleh())
                ->before(BatasEkspor::sebelum())
                ->action(fn () => $this->antrekan('duk', 'DUK')),
            Action::make('dukExcel')->label('DUK (Excel)')->icon(Heroicon::OutlinedTableCells)
                ->visible(fn (): bool => $this->boleh())
                ->before(BatasEkspor::sebelum())
                ->action(function () {
                    $baris = app(SusunLaporanKepegawaian::class)->duk(auth()->user());

                    return TulisXlsx::unduh('duk.xlsx', ['No', 'Nama', 'Golongan', 'TMT golongan', 'Jabatan', 'Masa kerja', 'Pendidikan', 'Usia'],
                        $baris->values()->map(fn (array $b, int $i): array => [$i + 1, $b['nama'], $b['golongan'], $b['tmt_golongan'], $b['jabatan'], $b['masa_kerja'], $b['pendidikan'], $b['usia']]));
                }),
            Action::make('pejabatPdf')->label('Rekap pejabat (PDF)')->icon(Heroicon::OutlinedDocumentArrowDown)
                ->visible(fn (): bool => $this->boleh())
                ->before(BatasEkspor::sebelum())
                ->action(fn () => $this->antrekan('pejabat', 'Rekap pejabat')),
            Action::make('pensiunExcel')->label('Pensiun ≤ 5 tahun (Excel)')->icon(Heroicon::OutlinedTableCells)
                ->visible(fn (): bool => $this->boleh())
                ->before(BatasEkspor::sebelum())
                ->action(function () {
                    $baris = app(SusunLaporanKepegawaian::class)->pensiun(auth()->user());

                    return TulisXlsx::unduh('pensiun-5-tahun.xlsx', ['Tahun', 'Nama', 'Jenis', 'Unit', 'Jabatan fungsional', 'Tanggal pensiun'],
                        $baris->map(fn (array $b): array => [$b['tahun'], $b['nama'], $b['jenis'], $b['unit'], $b['jabatan'], $b['tanggal_pensiun']]));
                }),
        ];
    }

    private function antrekan(string $jenis, string $judul): void
    {
        BuatPdfLaporan::dispatch($jenis, (string) auth()->id());

        Notification::make()->success()->title("{$judul} sedang dibuat")->body('Anda akan menerima notifikasi dengan tautan unduh.')->send();
    }
}

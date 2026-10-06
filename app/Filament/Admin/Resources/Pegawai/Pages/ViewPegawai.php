<?php

namespace App\Filament\Admin\Resources\Pegawai\Pages;

use App\Actions\Laporan\CetakProfilPegawai;
use App\Filament\Admin\Resources\Pegawai\Actions\UbahStatusAktifAction;
use App\Filament\Admin\Resources\Pegawai\PegawaiResource;
use App\Models\Pegawai;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ViewPegawai extends ViewRecord
{
    protected static string $resource = PegawaiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            UbahStatusAktifAction::make(),
            Action::make('cetakProfil')->label('Cetak profil')->icon(Heroicon::OutlinedPrinter)
                ->visible(fn (Pegawai $record): bool => (bool) auth()->user()?->can('view', $record))
                ->action(fn (Pegawai $record) => self::unduhProfil($record)),
        ];
    }

    public static function unduhProfil(Pegawai $pegawai): StreamedResponse
    {
        $aksi = app(CetakProfilPegawai::class);
        $isi = $aksi->handle(auth()->user(), $pegawai);

        return response()->streamDownload(function () use ($isi): void {
            echo $isi;
        }, $aksi->namaBerkas($pegawai), ['Content-Type' => 'application/pdf']);
    }
}

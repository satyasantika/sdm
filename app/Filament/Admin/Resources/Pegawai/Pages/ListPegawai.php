<?php

namespace App\Filament\Admin\Resources\Pegawai\Pages;

use App\Filament\Admin\Resources\Pegawai\PegawaiResource;
use App\Filament\Imports\PegawaiImporter;
use Filament\Actions\CreateAction;
use Filament\Actions\ImportAction;
use Filament\Resources\Pages\ListRecords;

class ListPegawai extends ListRecords
{
    protected static string $resource = PegawaiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ImportAction::make()
                ->importer(PegawaiImporter::class)
                ->label('Impor pegawai')
                ->chunkSize(100)
                ->visible(fn (): bool => (bool) auth()->user()?->can('pegawai.impor')),
            CreateAction::make(),
        ];
    }
}

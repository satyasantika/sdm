<?php

namespace App\Filament\Admin\Resources\Users\Pages;

use App\Filament\Admin\Resources\Users\UserResource;
use App\Filament\Imports\UserImporter;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\ImportAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ImportAction::make()
                ->importer(UserImporter::class)
                ->label('Impor pengguna')
                ->chunkSize(100)
                ->visible(fn (): bool => (bool) auth()->user()?->can('pengguna.kelola')),
            Action::make('tempel')
                ->label('Tempel dari Excel')
                ->icon(Heroicon::OutlinedClipboardDocumentList)
                ->color('gray')
                ->url(fn (): string => UserResource::getUrl('tempel'))
                ->visible(fn (): bool => (bool) auth()->user()?->can('pengguna.kelola')),
            CreateAction::make(),
        ];
    }
}

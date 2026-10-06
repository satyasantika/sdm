<?php

namespace App\Filament\Admin\Resources\Golongan\Pages;

use App\Filament\Admin\Resources\Golongan\GolonganResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListGolongan extends ListRecords
{
    protected static string $resource = GolonganResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}

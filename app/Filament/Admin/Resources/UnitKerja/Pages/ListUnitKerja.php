<?php

namespace App\Filament\Admin\Resources\UnitKerja\Pages;

use App\Filament\Admin\Resources\UnitKerja\UnitKerjaResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListUnitKerja extends ListRecords
{
    protected static string $resource = UnitKerjaResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}

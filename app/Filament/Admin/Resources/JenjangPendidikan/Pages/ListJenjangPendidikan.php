<?php

namespace App\Filament\Admin\Resources\JenjangPendidikan\Pages;

use App\Filament\Admin\Resources\JenjangPendidikan\JenjangPendidikanResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListJenjangPendidikan extends ListRecords
{
    protected static string $resource = JenjangPendidikanResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}

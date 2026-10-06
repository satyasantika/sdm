<?php

namespace App\Filament\Admin\Resources\JabatanFungsional\Pages;

use App\Filament\Admin\Resources\JabatanFungsional\JabatanFungsionalResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListJabatanFungsional extends ListRecords
{
    protected static string $resource = JabatanFungsionalResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}

<?php

namespace App\Filament\Admin\Resources\JenisDokumen\Pages;

use App\Filament\Admin\Resources\JenisDokumen\JenisDokumenResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListJenisDokumen extends ListRecords
{
    protected static string $resource = JenisDokumenResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}

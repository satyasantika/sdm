<?php

namespace App\Filament\Admin\Resources\JenisJabatanStruktural\Pages;

use App\Filament\Admin\Resources\JenisJabatanStruktural\JenisJabatanStrukturalResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListJenisJabatanStruktural extends ListRecords
{
    protected static string $resource = JenisJabatanStrukturalResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}

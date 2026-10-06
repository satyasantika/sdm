<?php

namespace App\Filament\Admin\Resources\JenisJabatanStruktural\Pages;

use App\Filament\Admin\Resources\JenisJabatanStruktural\JenisJabatanStrukturalResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditJenisJabatanStruktural extends EditRecord
{
    protected static string $resource = JenisJabatanStrukturalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}

<?php

namespace App\Filament\Admin\Resources\Golongan\Pages;

use App\Filament\Admin\Resources\Golongan\GolonganResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditGolongan extends EditRecord
{
    protected static string $resource = GolonganResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}

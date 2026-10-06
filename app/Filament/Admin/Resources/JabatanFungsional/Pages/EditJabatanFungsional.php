<?php

namespace App\Filament\Admin\Resources\JabatanFungsional\Pages;

use App\Filament\Admin\Resources\JabatanFungsional\JabatanFungsionalResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditJabatanFungsional extends EditRecord
{
    protected static string $resource = JabatanFungsionalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}

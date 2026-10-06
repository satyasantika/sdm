<?php

namespace App\Filament\Admin\Resources\JenisDokumen\Pages;

use App\Filament\Admin\Resources\JenisDokumen\JenisDokumenResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditJenisDokumen extends EditRecord
{
    protected static string $resource = JenisDokumenResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}

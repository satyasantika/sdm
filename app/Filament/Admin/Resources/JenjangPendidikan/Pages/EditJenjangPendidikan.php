<?php

namespace App\Filament\Admin\Resources\JenjangPendidikan\Pages;

use App\Filament\Admin\Resources\JenjangPendidikan\JenjangPendidikanResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditJenjangPendidikan extends EditRecord
{
    protected static string $resource = JenjangPendidikanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}

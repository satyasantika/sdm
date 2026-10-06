<?php

namespace App\Filament\Admin\Resources\StatusKepegawaian\Pages;

use App\Filament\Admin\Resources\StatusKepegawaian\StatusKepegawaianResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditStatusKepegawaian extends EditRecord
{
    protected static string $resource = StatusKepegawaianResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}

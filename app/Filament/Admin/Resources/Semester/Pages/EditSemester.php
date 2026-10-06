<?php

namespace App\Filament\Admin\Resources\Semester\Pages;

use App\Filament\Admin\Resources\Semester\SemesterResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSemester extends EditRecord
{
    protected static string $resource = SemesterResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}

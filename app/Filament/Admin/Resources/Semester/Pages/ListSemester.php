<?php

namespace App\Filament\Admin\Resources\Semester\Pages;

use App\Filament\Admin\Resources\Semester\SemesterResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSemester extends ListRecords
{
    protected static string $resource = SemesterResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}

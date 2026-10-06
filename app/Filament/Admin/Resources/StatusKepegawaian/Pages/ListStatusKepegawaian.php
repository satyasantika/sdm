<?php

namespace App\Filament\Admin\Resources\StatusKepegawaian\Pages;

use App\Filament\Admin\Resources\StatusKepegawaian\StatusKepegawaianResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListStatusKepegawaian extends ListRecords
{
    protected static string $resource = StatusKepegawaianResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}

<?php

namespace App\Filament\Admin\Resources\JenisSertifikasi\Pages;

use App\Filament\Admin\Resources\JenisSertifikasi\JenisSertifikasiResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListJenisSertifikasi extends ListRecords
{
    protected static string $resource = JenisSertifikasiResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}

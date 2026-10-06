<?php

namespace App\Filament\Admin\Resources\Bkd\Pages;

use App\Filament\Admin\Resources\Bkd\BkdResource;
use Filament\Resources\Pages\CreateRecord;

class CreateBkd extends CreateRecord
{
    protected static string $resource = BkdResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['sumber'] = 'manual';

        return $data;
    }
}

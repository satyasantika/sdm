<?php

namespace App\Filament\Admin\Resources\Bkd\Pages;

use App\Filament\Admin\Resources\Bkd\BkdResource;
use Filament\Resources\Pages\EditRecord;

class EditBkd extends EditRecord
{
    protected static string $resource = BkdResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['sumber'] = 'manual';

        return $data;
    }
}

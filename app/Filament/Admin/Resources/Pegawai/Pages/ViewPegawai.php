<?php

namespace App\Filament\Admin\Resources\Pegawai\Pages;

use App\Filament\Admin\Resources\Pegawai\Actions\UbahStatusAktifAction;
use App\Filament\Admin\Resources\Pegawai\PegawaiResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewPegawai extends ViewRecord
{
    protected static string $resource = PegawaiResource::class;

    protected function getHeaderActions(): array
    {
        return [EditAction::make(), UbahStatusAktifAction::make()];
    }
}

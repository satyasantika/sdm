<?php

namespace App\Filament\Admin\Resources\Pegawai\Pages;

use App\Actions\Pegawai\PerbaruiPegawai;
use App\Filament\Admin\Resources\Pegawai\Actions\UbahStatusAktifAction;
use App\Filament\Admin\Resources\Pegawai\PegawaiResource;
use App\Models\Pegawai;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditPegawai extends EditRecord
{
    protected static string $resource = PegawaiResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (PegawaiResource::adalahAdminProdi()) {
            unset($data['jenis_pegawai'], $data['prodi_id']);
        }

        return $data;
    }

    /** @param  array<string, mixed>  $data */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var Pegawai $record */
        return app(PerbaruiPegawai::class)->handle($record, $data);
    }

    protected function getHeaderActions(): array
    {
        return [ViewAction::make(), UbahStatusAktifAction::make()];
    }
}

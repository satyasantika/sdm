<?php

namespace App\Filament\Admin\Resources\Pegawai\Pages;

use App\Actions\Pegawai\BuatPegawai;
use App\Enums\JenisPegawai;
use App\Filament\Admin\Resources\Pegawai\PegawaiResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreatePegawai extends CreateRecord
{
    protected static string $resource = PegawaiResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (PegawaiResource::adalahAdminProdi()) {
            $data['jenis_pegawai'] = JenisPegawai::Dosen->value;
            $data['prodi_id'] = auth()->user()->prodi_id;
            $data['unit_kerja_id'] = null;
        }

        return $data;
    }

    /** @param  array<string, mixed>  $data */
    protected function handleRecordCreation(array $data): Model
    {
        return app(BuatPegawai::class)->handle($data);
    }
}

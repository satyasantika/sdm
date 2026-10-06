<?php

namespace App\Filament\Admin\Resources\JumlahMahasiswaProdi\Pages;

use App\Filament\Admin\Resources\JumlahMahasiswaProdi\JumlahMahasiswaProdiResource;
use App\Support\CakupanProdi;
use Filament\Resources\Pages\CreateRecord;

class CreateJumlahMahasiswaProdi extends CreateRecord
{
    protected static string $resource = JumlahMahasiswaProdiResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['diinput_oleh'] = auth()->id();

        // Admin-prodi selalu menulis ke prodinya sendiri (server-side).
        if ($prodi = CakupanProdi::terkunci(auth()->user())) {
            $data['prodi_id'] = $prodi;
        }

        return $data;
    }
}

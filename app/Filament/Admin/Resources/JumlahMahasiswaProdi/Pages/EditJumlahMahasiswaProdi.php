<?php

namespace App\Filament\Admin\Resources\JumlahMahasiswaProdi\Pages;

use App\Filament\Admin\Resources\JumlahMahasiswaProdi\JumlahMahasiswaProdiResource;
use App\Support\CakupanProdi;
use Filament\Resources\Pages\EditRecord;

class EditJumlahMahasiswaProdi extends EditRecord
{
    protected static string $resource = JumlahMahasiswaProdiResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['diinput_oleh'] = auth()->id();

        if ($prodi = CakupanProdi::terkunci(auth()->user())) {
            $data['prodi_id'] = $prodi;
        }

        return $data;
    }
}

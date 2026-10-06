<?php

namespace App\Filament\Admin\Resources\JumlahMahasiswaProdi\Pages;

use App\Filament\Admin\Resources\JumlahMahasiswaProdi\JumlahMahasiswaProdiResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListJumlahMahasiswaProdi extends ListRecords
{
    protected static string $resource = JumlahMahasiswaProdiResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}

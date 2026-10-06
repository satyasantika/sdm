<?php

namespace App\Filament\Schemas;

use App\Enums\JenisTautan;
use App\Enums\KategoriRekognisi;
use App\Enums\TingkatKegiatan;
use App\Filament\Forms\TautanBerkasField;
use App\Models\Pegawai;
use App\Models\Penghargaan;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Component;

/** Skema isian Penghargaan dipakai bersama oleh panel admin (RelationManager) dan swalayan (usulan). */
class PenghargaanForm
{
    /** @return array<int, Component|Field> */
    public static function components(Pegawai $pegawai): array
    {
        return [
            Select::make('kategori')->label('Kategori')->options(KategoriRekognisi::class)->required()->default(KategoriRekognisi::Penghargaan->value),
            TextInput::make('nama')->label('Nama')->required()->maxLength(200),
            TextInput::make('pemberi')->label('Pemberi')->maxLength(150),
            Select::make('tingkat')->label('Tingkat')->options(TingkatKegiatan::class)->required(),
            DatePicker::make('tanggal')->label('Tanggal'),
            TextInput::make('nomor_sk')->label('Nomor SK')->maxLength(100),
            TautanBerkasField::make('penghargaan', JenisTautan::Penghargaan, 'Tautan bukti')->columnSpanFull(),
        ];
    }
}

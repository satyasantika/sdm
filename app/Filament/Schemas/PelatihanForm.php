<?php

namespace App\Filament\Schemas;

use App\Enums\JenisPelatihan;
use App\Enums\JenisTautan;
use App\Enums\TingkatKegiatan;
use App\Filament\Forms\TautanBerkasField;
use App\Models\Pegawai;
use App\Models\Pelatihan;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Component;

/** Skema isian Pelatihan dipakai bersama oleh panel admin (RelationManager) dan swalayan (usulan). */
class PelatihanForm
{
    /** @return array<int, Component|Field> */
    public static function components(Pegawai $pegawai): array
    {
        return [
            TextInput::make('nama')->label('Nama')->required()->maxLength(200),
            Select::make('jenis')->label('Jenis')->options(JenisPelatihan::class)->required(),
            TextInput::make('penyelenggara')->label('Penyelenggara')->maxLength(150),
            Select::make('tingkat')->label('Tingkat')->options(TingkatKegiatan::class),
            DatePicker::make('tanggal_mulai')->label('Tanggal mulai')->required(),
            DatePicker::make('tanggal_selesai')->label('Tanggal selesai'),
            TextInput::make('jumlah_jam')->label('Jumlah jam')->numeric()->minValue(1)->maxValue(2000),
            TautanBerkasField::make('pelatihan', JenisTautan::Pelatihan, 'Tautan bukti')->columnSpanFull(),
        ];
    }
}

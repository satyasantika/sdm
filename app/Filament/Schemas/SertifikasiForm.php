<?php

namespace App\Filament\Schemas;

use App\Enums\JenisTautan;
use App\Filament\Forms\TautanBerkasField;
use App\Models\JenisSertifikasi;
use App\Models\Pegawai;
use App\Models\Sertifikasi;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Utilities\Get;

/** Skema isian Sertifikasi dipakai bersama oleh panel admin (RelationManager) dan swalayan (usulan). */
class SertifikasiForm
{
    /** @return array<int, Component|Field> */
    public static function components(Pegawai $pegawai): array
    {
        return [
            Select::make('jenis_sertifikasi_id')->label('Jenis')->required()->live()
                ->options(fn (): array => JenisSertifikasi::query()->orderBy('nama')->pluck('nama', 'id')->all()),
            TextInput::make('nama')->label('Nama sertifikat')->required()->maxLength(200),
            TextInput::make('nomor_sertifikat')->label('Nomor sertifikat')->maxLength(100),
            TextInput::make('nomor_registrasi')->label('Nomor registrasi')->maxLength(100),
            TextInput::make('bidang')->label('Bidang')->maxLength(150),
            TextInput::make('penerbit')->label('Penerbit')->maxLength(150),
            TextInput::make('tahun')->label('Tahun')->numeric()->minValue(1950)->maxValue((int) now()->year),
            DatePicker::make('tanggal_terbit')->label('Tanggal terbit'),
            DatePicker::make('tanggal_kedaluwarsa')->label('Tanggal kedaluwarsa')
                ->required(fn (Get $get): bool => (bool) JenisSertifikasi::whereKey($get('jenis_sertifikasi_id'))->value('punya_masa_berlaku')),
            TautanBerkasField::make('sertifikat', JenisTautan::Sertifikat, 'Tautan sertifikat')->columnSpanFull(),
        ];
    }
}

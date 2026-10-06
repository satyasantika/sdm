<?php

namespace App\Filament\Schemas;

use App\Enums\HubunganKeluarga;
use App\Enums\JenisKelamin;
use App\Models\Keluarga;
use App\Models\Pegawai;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Component;

/** Skema isian Keluarga dipakai bersama oleh panel admin (RelationManager) dan swalayan (usulan). */
class KeluargaForm
{
    /** @return array<int, Component|Field> */
    public static function components(Pegawai $pegawai): array
    {
        return [
            Select::make('hubungan')->label('Hubungan')->options(HubunganKeluarga::class)->required()->live(),
            TextInput::make('nama')->label('Nama')->required()->maxLength(150),
            TextInput::make('nik')->label('NIK')->regex('/^\d{16}$/')
                ->placeholder(fn (?Keluarga $record): string => $record?->getRawOriginal('nik') ? 'Terisi — kosongkan bila tidak diubah' : '')
                ->afterStateHydrated(fn (TextInput $component) => $component->state(null))
                ->dehydrated(fn (?string $state): bool => filled($state))
                ->validationMessages(['regex' => 'NIK harus 16 digit angka.']),
            TextInput::make('tempat_lahir')->label('Tempat lahir')->maxLength(80),
            DatePicker::make('tanggal_lahir')->label('Tanggal lahir'),
            Select::make('jenis_kelamin')->label('Jenis kelamin')->options(JenisKelamin::class),
            TextInput::make('pekerjaan')->label('Pekerjaan')->maxLength(100),
            Toggle::make('status_tunjangan')->label('Masuk daftar tunjangan'),
            DatePicker::make('tanggal_nikah')->label('Tanggal nikah')
                ->visible(fn ($get): bool => in_array($get('hubungan'), ['suami', 'istri'], true)),
        ];
    }
}

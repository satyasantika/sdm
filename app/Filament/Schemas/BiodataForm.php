<?php

namespace App\Filament\Schemas;

use App\Models\Pegawai;
use App\Support\RegistriTargetUsulan;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;

/** Kolom biodata yang boleh diusulkan (RegistriTargetUsulan 'pegawai'). NIK/NPWP/rekening kosong; isi hanya bila ingin mengoreksi. */
class BiodataForm
{
    /** @return array<int, Field> */
    public static function components(Pegawai $pegawai): array
    {
        return [
            TextInput::make('gelar_depan')->label('Gelar depan')->maxLength(50),
            TextInput::make('gelar_belakang')->label('Gelar belakang')->maxLength(80),
            TextInput::make('tempat_lahir')->label('Tempat lahir')->maxLength(80),
            DatePicker::make('tanggal_lahir')->label('Tanggal lahir'),
            Select::make('agama')->label('Agama')->options(array_combine(['Islam', 'Kristen', 'Katolik', 'Hindu', 'Buddha', 'Konghucu'], ['Islam', 'Kristen', 'Katolik', 'Hindu', 'Buddha', 'Konghucu'])),
            Select::make('status_perkawinan')->label('Status perkawinan')->options([
                'belum_kawin' => 'Belum kawin', 'kawin' => 'Kawin', 'cerai_hidup' => 'Cerai hidup', 'cerai_mati' => 'Cerai mati',
            ]),
            Textarea::make('alamat')->label('Alamat')->columnSpanFull(),
            TextInput::make('no_hp')->label('No. HP')->maxLength(20),
            TextInput::make('email_pribadi')->label('Surel pribadi')->email()->maxLength(150),
            TextInput::make('nidn')->label('NIDN')->regex('/^\d{10}$/'),
            TextInput::make('nuptk')->label('NUPTK')->regex('/^\d{16}$/'),
            TextInput::make('nik')->label('NIK (isi hanya bila mengoreksi)')->regex('/^\d{16}$/'),
            TextInput::make('npwp')->label('NPWP (isi hanya bila mengoreksi)')->maxLength(30),
            TextInput::make('nama_bank')->label('Nama bank')->maxLength(60),
            TextInput::make('nomor_rekening')->label('Nomor rekening (isi hanya bila mengoreksi)')->maxLength(30),
        ];
    }

    /**
     * Nilai awal modal: kolom saat ini; kolom sensitif sengaja kosong.
     *
     * @return array<string, mixed>
     */
    public static function nilaiAwal(Pegawai $pegawai): array
    {
        $awal = RegistriTargetUsulan::snapshot('pegawai', $pegawai);

        foreach (RegistriTargetUsulan::kolomSensitif('pegawai') as $kolom) {
            $awal[$kolom] = null;
        }

        return $awal;
    }
}

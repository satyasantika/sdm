<?php

namespace App\Filament\Schemas;

use App\Enums\JenisTautan;
use App\Filament\Forms\TautanBerkasField;
use App\Models\JenisJabatanStruktural;
use App\Models\Pegawai;
use App\Models\UnitKerja;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Component;

/** Skema isian Struktural dipakai bersama oleh panel admin (RelationManager) dan swalayan (usulan). */
class StrukturalForm
{
    /** @return array<int, Component|Field> */
    public static function components(Pegawai $pegawai): array
    {
        return [
            Select::make('jenis_jabatan_struktural_id')->label('Jabatan')->required()->searchable()
                ->options(fn (): array => JenisJabatanStruktural::query()->where('is_aktif', true)->orderBy('urutan')->pluck('nama', 'id')->all()),
            Select::make('unit_kerja_id')->label('Unit kerja')->searchable()
                ->options(fn (): array => UnitKerja::query()->where('is_aktif', true)->orderBy('nama')->pluck('nama', 'id')->all()),
            DatePicker::make('tmt_mulai')->label('TMT mulai')->required(),
            DatePicker::make('tmt_selesai')->label('TMT selesai')->helperText('Kosongkan bila masih menjabat'),
            TextInput::make('nomor_sk')->label('Nomor SK')->required()->maxLength(100),
            DatePicker::make('tanggal_sk')->label('Tanggal SK'),
            TautanBerkasField::make('sk', JenisTautan::Sk, 'Tautan berkas SK')->columnSpanFull(),
        ];
    }
}

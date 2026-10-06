<?php

namespace App\Filament\Schemas;

use App\Enums\JenisTautan;
use App\Enums\Peran;
use App\Filament\Forms\TautanBerkasField;
use App\Models\JabatanFungsional;
use App\Models\Pegawai;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Component;

/** Skema isian JabatanFungsional dipakai bersama oleh panel admin (RelationManager) dan swalayan (usulan). */
class JabatanFungsionalForm
{
    /** @return array<int, Component|Field> */
    public static function components(Pegawai $pegawai): array
    {
        return [
            Select::make('jabatan_fungsional_id')->label('Jabatan')->required()->searchable()
                ->options(fn (): array => JabatanFungsional::query()->aktif()
                    ->where('kelompok', $pegawai->jenis_pegawai->value)->orderBy('urutan')->pluck('nama', 'id')->all()),
            DatePicker::make('tmt')->label('TMT')->required(),
            TextInput::make('nomor_sk')->label('Nomor SK')->required()->maxLength(100),
            DatePicker::make('tanggal_sk')->label('Tanggal SK'),
            TextInput::make('angka_kredit')->label('Angka kredit')->numeric()->minValue(0)
                ->helperText('Isi bila tercantum di SK'),
            Toggle::make('is_koreksi')->label('Koreksi data (boleh menurunkan jenjang)')
                ->visible(fn (): bool => (bool) auth()->user()?->hasAnyRole([Peran::SuperAdmin->value, Peran::AdminKepegawaian->value])),
            TextInput::make('keterangan')->label('Keterangan')->maxLength(255)->columnSpanFull(),
            TautanBerkasField::make('sk', JenisTautan::Sk, 'Tautan berkas SK')->columnSpanFull(),
        ];
    }
}

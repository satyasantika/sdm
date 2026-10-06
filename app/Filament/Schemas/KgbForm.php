<?php

namespace App\Filament\Schemas;

use App\Enums\JenisTautan;
use App\Filament\Forms\TautanBerkasField;
use App\Models\Golongan;
use App\Models\Pegawai;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Component;

/** Skema isian Kgb dipakai bersama oleh panel admin (RelationManager) dan swalayan (usulan). */
class KgbForm
{
    /** @return array<int, Component|Field> */
    public static function components(Pegawai $pegawai): array
    {
        $jenis = $pegawai->statusKepegawaian->jenis_golongan;

        return [
            DatePicker::make('tmt')->label('TMT')->required(),
            Select::make('golongan_id')->label('Golongan')->searchable()
                ->options(fn (): array => Golongan::query()->aktif()->urut()
                    ->when($jenis, fn ($q) => $q->where('jenis', $jenis->value))
                    ->get()->mapWithKeys(fn (Golongan $g): array => [$g->id => $g->label])->all()),
            TextInput::make('gaji_pokok')->label('Gaji pokok (Rp)')->numeric()->minValue(0)->step('0.01'),
            TextInput::make('nomor_sk')->label('Nomor SK')->required()->maxLength(100),
            DatePicker::make('tanggal_sk')->label('Tanggal SK'),
            TextInput::make('masa_kerja_tahun')->label('Masa kerja (tahun)')->numeric()->minValue(0)->maxValue(60),
            TextInput::make('masa_kerja_bulan')->label('Masa kerja (bulan)')->numeric()->minValue(0)->maxValue(11),
            TautanBerkasField::make('sk', JenisTautan::Sk, 'Tautan berkas SK KGB')->columnSpanFull(),
        ];
    }
}

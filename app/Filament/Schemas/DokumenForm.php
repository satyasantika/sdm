<?php

namespace App\Filament\Schemas;

use App\Enums\JenisTautan;
use App\Filament\Forms\TautanBerkasField;
use App\Models\JenisDokumen;
use App\Models\Pegawai;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Utilities\Get;

/** Skema isian dokumen kepegawaian dipakai bersama panel admin dan swalayan. */
class DokumenForm
{
    /** @return array<int, Component|Field> */
    public static function components(Pegawai $pegawai): array
    {
        return [
            Select::make('jenis_dokumen_id')->label('Jenis dokumen')->required()->live()->searchable()
                ->options(fn (): array => JenisDokumen::query()->orderBy('nama')->pluck('nama', 'id')->all()),
            TextInput::make('nomor')->label('Nomor')->maxLength(100)
                ->helperText('Kosongkan untuk KTP/NPWP/rekening; nomor identitas hanya disimpan terenkripsi di data pegawai.')
                ->hidden(fn (Get $get): bool => (bool) JenisDokumen::whereKey($get('jenis_dokumen_id'))->value('is_identitas')),
            DatePicker::make('tanggal_terbit')->label('Tanggal terbit'),
            DatePicker::make('tanggal_kedaluwarsa')->label('Tanggal kedaluwarsa')
                ->required(fn (Get $get): bool => (bool) JenisDokumen::whereKey($get('jenis_dokumen_id'))->value('punya_masa_berlaku')),
            TextInput::make('catatan')->label('Catatan')->maxLength(255)->columnSpanFull(),
            TautanBerkasField::make('dokumen', JenisTautan::DokumenKepegawaian, 'Tautan dokumen')->columnSpanFull(),
        ];
    }
}

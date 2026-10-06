<?php

namespace App\Filament\Schemas;

use App\Enums\JenisTautan;
use App\Filament\Forms\TautanBerkasField;
use App\Models\JenjangPendidikan;
use App\Models\Pegawai;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Component;

/** Skema isian Pendidikan dipakai bersama oleh panel admin (RelationManager) dan swalayan (usulan). */
class PendidikanForm
{
    /** @return array<int, Component|Field> */
    public static function components(Pegawai $pegawai): array
    {
        return [
            Select::make('jenjang_pendidikan_id')->label('Jenjang')->required()
                ->options(fn (): array => JenjangPendidikan::query()->orderBy('urutan')->pluck('nama', 'id')->all()),
            TextInput::make('nama_pt')->label('Perguruan tinggi')->required()->maxLength(150),
            TextInput::make('negara')->label('Negara')->default('Indonesia')->maxLength(60),
            TextInput::make('nama_prodi')->label('Program studi')->maxLength(150),
            TextInput::make('bidang_ilmu')->label('Bidang ilmu')->maxLength(150),
            TextInput::make('gelar')->label('Gelar')->maxLength(30),
            TextInput::make('tahun_masuk')->label('Tahun masuk')->numeric()->minValue(1950)->maxValue((int) now()->year),
            TextInput::make('tahun_lulus')->label('Tahun lulus')->numeric()->minValue(1950)->maxValue((int) now()->year),
            TextInput::make('nomor_ijazah')->label('Nomor ijazah')->maxLength(100),
            TextInput::make('ipk')->label('IPK')->numeric()->minValue(0)->maxValue(4)->step('0.01'),
            TextInput::make('judul_tugas_akhir')->label('Judul tugas akhir')->maxLength(500)->columnSpanFull(),
            TautanBerkasField::make('ijazah', JenisTautan::Ijazah, 'Tautan ijazah')->columnSpanFull(),
            TautanBerkasField::make('transkrip', JenisTautan::Transkrip, 'Tautan transkrip')->columnSpanFull(),
        ];
    }
}

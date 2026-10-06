<?php

namespace App\Filament\Schemas;

use App\Enums\JenisStudiLanjut;
use App\Enums\JenisTautan;
use App\Enums\StatusAktifPegawai;
use App\Enums\StatusStudiLanjut;
use App\Filament\Forms\TautanBerkasField;
use App\Models\JenjangPendidikan;
use App\Models\Pegawai;
use App\Models\StudiLanjut;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Component;

/** Skema isian StudiLanjut dipakai bersama oleh panel admin (RelationManager) dan swalayan (usulan). */
class StudiLanjutForm
{
    /** @return array<int, Component|Field> */
    public static function components(Pegawai $pegawai): array
    {
        return [
            Select::make('jenis')->label('Jenis')->options(JenisStudiLanjut::class)->required()->live(),
            Select::make('jenjang_pendidikan_id')->label('Jenjang')->required()
                ->options(fn (): array => JenjangPendidikan::query()->orderBy('urutan')->pluck('nama', 'id')->all()),
            TextInput::make('nama_pt')->label('Perguruan tinggi')->required()->maxLength(150),
            TextInput::make('negara')->label('Negara')->default('Indonesia')->maxLength(60),
            TextInput::make('nama_prodi')->label('Program studi')->maxLength(150),
            TextInput::make('sumber_biaya')->label('Sumber biaya')->maxLength(100)->placeholder('BPI, LPDP, mandiri'),
            TextInput::make('nomor_sk')->label('Nomor SK')->maxLength(100),
            DatePicker::make('tanggal_mulai')->label('Tanggal mulai')->required(),
            DatePicker::make('tanggal_selesai_rencana')->label('Rencana selesai'),
            DatePicker::make('tanggal_selesai_aktual')->label('Selesai aktual'),
            Select::make('status')->label('Status')->options(StatusStudiLanjut::class)->required()->default(StatusStudiLanjut::Berjalan->value)->live(),
            Checkbox::make('sinkron_status')->label(self::labelSinkron($pegawai))->default(true)->dehydrated(true)
                ->visible(fn ($get): bool => self::bolehSinkron($get('jenis'))),
            TautanBerkasField::make('sk', JenisTautan::Sk, 'Tautan SK tugas/izin belajar')->columnSpanFull(),
        ];
    }

    private static function labelSinkron(Pegawai $pegawai): string
    {
        return $pegawai->status_aktif === StatusAktifPegawai::TugasBelajar
            ? 'Kembalikan status pegawai menjadi aktif bila studi selesai/berhenti'
            : 'Ubah status pegawai menjadi tugas belajar';
    }

    private static function bolehSinkron(mixed $jenis): bool
    {
        $jenis = $jenis instanceof JenisStudiLanjut ? $jenis->value : $jenis;

        return $jenis === JenisStudiLanjut::TugasBelajar->value && (bool) auth()->user()?->can('pegawai.ubah');
    }
}

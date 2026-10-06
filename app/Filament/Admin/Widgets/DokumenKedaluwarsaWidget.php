<?php

namespace App\Filament\Admin\Widgets;

use App\Enums\StatusBerlaku;
use App\Filament\Admin\Resources\DokumenPegawai\DokumenPegawaiResource;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/** Hitungan dokumen per status berlaku (belum dipasang di dasbor; dipakai F9). */
class DokumenKedaluwarsaWidget extends StatsOverviewWidget
{
    protected ?string $heading = 'Dokumen Kepegawaian';

    public static function canView(): bool
    {
        return (bool) auth()->user()?->can('dokumen.lihat');
    }

    /** @return array<int, Stat> */
    protected function getStats(): array
    {
        $hitung = fn (StatusBerlaku $status): int => DokumenPegawaiResource::getEloquentQuery()->where('status_berlaku', $status->value)->count();

        return [
            Stat::make('Berlaku', $hitung(StatusBerlaku::Berlaku))->color('success'),
            Stat::make('Segera berakhir', $hitung(StatusBerlaku::SegeraBerakhir))->color('warning'),
            Stat::make('Kedaluwarsa', $hitung(StatusBerlaku::Kedaluwarsa))->color('danger'),
        ];
    }
}

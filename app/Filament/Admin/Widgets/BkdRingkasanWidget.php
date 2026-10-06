<?php

namespace App\Filament\Admin\Widgets;

use App\Filament\Admin\Resources\Bkd\Pages\ListBkd;
use App\Support\BkdRingkasan;
use Filament\Widgets\Concerns\InteractsWithPageTable;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class BkdRingkasanWidget extends StatsOverviewWidget
{
    use InteractsWithPageTable;

    protected function getTablePage(): string
    {
        return ListBkd::class;
    }

    /** @return array<int, Stat> */
    protected function getStats(): array
    {
        $semesterId = $this->tableFilters['semester_id']['value'] ?? null;
        $prodiId = $this->tableFilters['prodi']['value'] ?? null;
        $ringkasan = BkdRingkasan::hitung($semesterId, $prodiId, auth()->user());

        return [
            Stat::make('Dosen tetap', $ringkasan['dosen_tetap']),
            Stat::make('Memenuhi', $ringkasan['memenuhi'])->color('success'),
            Stat::make('Tidak memenuhi', $ringkasan['tidak_memenuhi'])->color('danger'),
            Stat::make('Belum ada data BKD', $ringkasan['belum_ada_data'])->color('warning'),
        ];
    }
}

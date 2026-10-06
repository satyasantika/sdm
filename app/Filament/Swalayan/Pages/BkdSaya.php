<?php

namespace App\Filament\Swalayan\Pages;

use App\Models\Bkd;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;

class BkdSaya extends Page
{
    protected string $view = 'filament.swalayan.pages.bkd-saya';

    protected static ?string $title = 'BKD Saya';

    protected static ?string $slug = 'bkd-saya';

    protected static ?int $navigationSort = 4;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBarSquare;

    /** @return Collection<int, Bkd> hanya milik sendiri */
    public function riwayat(): Collection
    {
        return Bkd::query()
            ->with('semester')
            ->whereHas('pegawai', fn ($q) => $q->where('user_id', auth()->id()))
            ->get()
            ->sortByDesc(fn (Bkd $b): string => $b->semester->kode)
            ->values();
    }
}

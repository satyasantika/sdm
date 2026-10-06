<?php

namespace App\Filament\Admin\Resources\Pengingat\Pages;

use App\Filament\Admin\Resources\Pengingat\PengingatResource;
use App\Filament\Exports\PengingatExporter;
use Filament\Actions\ExportAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListPengingat extends ListRecords
{
    protected static string $resource = PengingatResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ExportAction::make()
                ->exporter(PengingatExporter::class)
                ->label('Ekspor jatuh tempo 180 hari')
                ->fileDisk('tmp')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->whereDate('tanggal_jatuh_tempo', '<=', now()->addDays(180)->toDateString())
                    ->whereIn('status', ['aktif', 'lewat_tempo']))
                ->visible(fn (): bool => (bool) auth()->user()?->can('laporan.ekspor')),
        ];
    }
}

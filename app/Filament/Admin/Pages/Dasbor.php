<?php

namespace App\Filament\Admin\Pages;

use App\Enums\Peran;
use App\Filament\Admin\Widgets\DasborStatistikWidget;
use App\Filament\Admin\Widgets\DokumenKedaluwarsaWidget;
use App\Filament\Admin\Widgets\DosenPerProdiWidget;
use App\Filament\Admin\Widgets\JabatanChartWidget;
use App\Filament\Admin\Widgets\PejabatAktifWidget;
use App\Filament\Admin\Widgets\PendidikanChartWidget;
use App\Filament\Admin\Widgets\PensiunLimaTahunWidget;
use App\Models\Prodi;
use Filament\Forms\Components\Select;
use Filament\Pages\Dashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Schemas\Schema;

class Dasbor extends Dashboard
{
    use HasFiltersForm;

    protected static ?string $title = 'Dasbor';

    public function filtersForm(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('prodi_id')->label('Program studi')->placeholder('Seluruh fakultas')
                ->options(fn (): array => Prodi::opsiAktif())
                ->visible(fn (): bool => ! (auth()->user()?->hasRole(Peran::AdminProdi->value) && ! auth()->user()->hasAnyRole([Peran::SuperAdmin->value, Peran::AdminKepegawaian->value]))),
        ]);
    }

    public function getWidgets(): array
    {
        return [
            DasborStatistikWidget::class,
            JabatanChartWidget::class,
            PendidikanChartWidget::class,
            DosenPerProdiWidget::class,
            PensiunLimaTahunWidget::class,
            PejabatAktifWidget::class,
            DokumenKedaluwarsaWidget::class,
        ];
    }

    public function getColumns(): int|array
    {
        return ['default' => 1, 'lg' => 2];
    }
}

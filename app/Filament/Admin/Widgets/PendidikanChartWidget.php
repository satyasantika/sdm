<?php

namespace App\Filament\Admin\Widgets;

use App\Actions\Laporan\HitungStatistikDasbor;
use App\Filament\Admin\Widgets\Concerns\MemakaiProdiDasbor;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

class PendidikanChartWidget extends ChartWidget
{
    use InteractsWithPageFilters;
    use MemakaiProdiDasbor;

    protected ?string $heading = 'Dosen per pendidikan tertinggi';

    protected function getType(): string
    {
        return 'doughnut';
    }

    /** @return array<string, mixed> */
    protected function getData(): array
    {
        $data = app(HitungStatistikDasbor::class)->handle($this->prodiId())['dosen_per_pendidikan'];

        return [
            'datasets' => [['data' => array_values($data)]],
            'labels' => array_keys($data),
        ];
    }
}

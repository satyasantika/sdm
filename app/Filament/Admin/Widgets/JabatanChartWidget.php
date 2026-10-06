<?php

namespace App\Filament\Admin\Widgets;

use App\Actions\Laporan\HitungStatistikDasbor;
use App\Filament\Admin\Widgets\Concerns\MemakaiProdiDasbor;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

class JabatanChartWidget extends ChartWidget
{
    use InteractsWithPageFilters;
    use MemakaiProdiDasbor;

    protected ?string $heading = 'Dosen per jabatan fungsional';

    protected function getType(): string
    {
        return 'bar';
    }

    /** @return array<string, mixed> */
    protected function getData(): array
    {
        $data = app(HitungStatistikDasbor::class)->handle($this->prodiId())['dosen_per_jabatan'];

        return [
            'datasets' => [['label' => 'Dosen', 'data' => array_values($data)]],
            'labels' => array_keys($data),
        ];
    }
}

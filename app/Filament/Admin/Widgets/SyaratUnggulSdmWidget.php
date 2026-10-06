<?php

namespace App\Filament\Admin\Widgets;

use App\Actions\Laporan\HitungSyaratUnggulSdm;
use App\Models\Prodi;
use Filament\Widgets\Widget;

/** LAP-10: indikator syarat unggul SDM (DTPS doktor, lektor, lektor kepala) per prodi. */
class SyaratUnggulSdmWidget extends Widget
{
    protected string $view = 'filament.admin.widgets.syarat-unggul-sdm';

    protected int|string|array $columnSpan = 'full';

    public ?string $prodiId = null;

    /** @return array<string, mixed> */
    protected function getViewData(): array
    {
        $prodi = $this->prodiId ? Prodi::find($this->prodiId) : null;

        return [
            'prodi' => $prodi,
            'hasil' => $prodi ? app(HitungSyaratUnggulSdm::class)->handle($prodi) : null,
        ];
    }

    public static function canView(): bool
    {
        return (bool) auth()->user()?->can('laporan.lihat');
    }
}

<?php

namespace App\Filament\Admin\Widgets;

use App\Actions\Laporan\HitungStatistikDasbor;
use App\Enums\StatusPengingat;
use App\Enums\StatusUsulan;
use App\Filament\Admin\Widgets\Concerns\MemakaiProdiDasbor;
use App\Models\Pengingat;
use App\Models\UsulanPerubahan;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;

class DasborStatistikWidget extends StatsOverviewWidget
{
    use InteractsWithPageFilters;
    use MemakaiProdiDasbor;

    protected int|string|array $columnSpan = 'full';

    /** @return array<int, Stat> */
    protected function getStats(): array
    {
        $prodiId = $this->prodiId();
        $s = app(HitungStatistikDasbor::class)->handle($prodiId);

        $perProdi = fn (Builder $q) => $prodiId ? $q->whereHas('pegawai', fn (Builder $p) => $p->where('prodi_id', $prodiId)) : $q;

        $usulan = $perProdi(UsulanPerubahan::query()->where('status', StatusUsulan::Diajukan->value))->count();
        $pengingat = $perProdi(Pengingat::query()->whereIn('status', [StatusPengingat::Aktif->value, StatusPengingat::LewatTempo->value])
            ->whereDate('tanggal_jatuh_tempo', '<=', now()->addDays(30)->toDateString()))->count();

        return [
            Stat::make('Dosen aktif', $s['jumlah_dosen']),
            Stat::make('Tendik aktif', $s['jumlah_tendik']),
            Stat::make('Dosen S3', $s['persen_s3'].'%')->color('success'),
            Stat::make('Dosen ber-serdos', $s['persen_serdos'].'%')->color('success'),
            Stat::make('Usulan menunggu', $usulan)->color('warning'),
            Stat::make('Pengingat ≤ 30 hari', $pengingat)->color('danger'),
        ];
    }
}

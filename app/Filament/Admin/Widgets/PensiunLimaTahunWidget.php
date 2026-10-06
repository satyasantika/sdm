<?php

namespace App\Filament\Admin\Widgets;

use App\Enums\StatusAktifPegawai;
use App\Filament\Admin\Widgets\Concerns\MemakaiProdiDasbor;
use App\Models\Pegawai;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

/** LAP-07: pegawai yang akan pensiun ≤ 5 tahun. */
class PensiunLimaTahunWidget extends TableWidget
{
    use InteractsWithPageFilters;
    use MemakaiProdiDasbor;

    protected static ?string $heading = 'Pensiun ≤ 5 tahun';

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => Pegawai::query()
                ->with(['prodi'])
                ->whereIn('status_aktif', [StatusAktifPegawai::Aktif->value, StatusAktifPegawai::TugasBelajar->value])
                ->whereBetween('tanggal_pensiun', [now()->toDateString(), now()->addYears(5)->toDateString()])
                ->when($this->prodiId(), fn (Builder $q, string $id) => $q->where('prodi_id', $id))
                ->orderBy('tanggal_pensiun'))
            ->paginated([10, 25])
            ->columns([
                TextColumn::make('nama_bergelar')->label('Nama'),
                TextColumn::make('jenis_pegawai')->label('Jenis')->badge(),
                TextColumn::make('prodi.nama')->label('Prodi')->placeholder('-'),
                TextColumn::make('tanggal_pensiun')->label('Pensiun')->date('d F Y'),
            ]);
    }
}

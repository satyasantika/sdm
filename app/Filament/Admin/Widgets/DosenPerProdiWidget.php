<?php

namespace App\Filament\Admin\Widgets;

use App\Enums\JenisPegawai;
use App\Enums\StatusAktifPegawai;
use App\Filament\Admin\Widgets\Concerns\MemakaiProdiDasbor;
use App\Models\Prodi;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class DosenPerProdiWidget extends TableWidget
{
    use InteractsWithPageFilters;
    use MemakaiProdiDasbor;

    protected static ?string $heading = 'Dosen per program studi';

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => Prodi::query()
                ->when($this->prodiId(), fn (Builder $q, string $id) => $q->whereKey($id))
                ->withCount(['pegawai as dosen_aktif' => fn (Builder $q) => $q->where('jenis_pegawai', JenisPegawai::Dosen->value)->where('status_aktif', StatusAktifPegawai::Aktif->value)])
                ->orderBy('nama'))
            ->paginated(false)
            ->columns([
                TextColumn::make('nama')->label('Program studi'),
                TextColumn::make('jenjang')->label('Jenjang')->badge(),
                TextColumn::make('dosen_aktif')->label('Dosen aktif'),
            ]);
    }
}

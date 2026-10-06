<?php

namespace App\Filament\Admin\Widgets;

use App\Models\RiwayatJabatanStruktural;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

/** Pejabat aktif per jenis jabatan (belum dipasang di dasbor; dipakai F9). */
class PejabatAktifWidget extends TableWidget
{
    protected static ?string $heading = 'Pejabat Aktif';

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return (bool) auth()->user()?->can('riwayat.lihat');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => RiwayatJabatanStruktural::query()->aktif()->with(['pegawai', 'jenisJabatanStruktural', 'unitKerja']))
            ->paginated(false)
            ->columns([
                TextColumn::make('jenisJabatanStruktural.nama')->label('Jabatan'),
                TextColumn::make('pegawai.nama_bergelar')->label('Pejabat'),
                TextColumn::make('unitKerja.nama')->label('Unit kerja')->placeholder('-'),
                TextColumn::make('periode')->label('Periode'),
            ]);
    }
}

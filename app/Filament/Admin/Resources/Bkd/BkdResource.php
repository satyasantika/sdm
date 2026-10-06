<?php

namespace App\Filament\Admin\Resources\Bkd;

use App\Enums\KesimpulanBkd;
use App\Enums\Peran;
use App\Filament\Admin\Resources\Bkd\Pages\ListBkd;
use App\Models\Bkd;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class BkdResource extends Resource
{
    protected static ?string $model = Bkd::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBarSquare;

    protected static string|UnitEnum|null $navigationGroup = 'BKD';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Rekap BKD';

    protected static ?string $modelLabel = 'BKD';

    protected static ?string $pluralModelLabel = 'BKD';

    /** Admin-prodi hanya BKD dosen prodinya. */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with(['pegawai.prodi', 'pegawai.jabatanFungsional', 'semester']);
        $user = auth()->user();

        if ($user && $user->hasRole(Peran::AdminProdi->value) && ! $user->hasAnyRole([Peran::SuperAdmin->value, Peran::AdminKepegawaian->value])) {
            $query->whereHas('pegawai', fn (Builder $q) => $user->prodi_id ? $q->where('prodi_id', $user->prodi_id) : $q->whereRaw('1 = 0'));
        }

        return $query;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('pegawai.nama_bergelar')->label('Dosen')
                    ->searchable(query: fn (Builder $q, string $cari): Builder => $q->whereHas('pegawai', fn (Builder $p) => $p->where('nama', 'like', "%{$cari}%"))),
                TextColumn::make('pegawai.nidn')->label('NIDN')->placeholder('-'),
                TextColumn::make('semester.label')->label('Semester'),
                TextColumn::make('total_sks')->label('Total SKS')->placeholder('-'),
                TextColumn::make('kesimpulan')->label('Kesimpulan')->badge(),
                TextColumn::make('sumber')->label('Sumber'),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ListBkd::route('/')];
    }

    /** @return list<string> */
    public static function nilaiKesimpulan(): array
    {
        return array_map(fn (KesimpulanBkd $k) => $k->value, KesimpulanBkd::cases());
    }
}

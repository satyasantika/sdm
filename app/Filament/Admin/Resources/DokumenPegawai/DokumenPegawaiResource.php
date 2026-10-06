<?php

namespace App\Filament\Admin\Resources\DokumenPegawai;

use App\Enums\Peran;
use App\Enums\StatusBerlaku;
use App\Filament\Admin\Resources\DokumenPegawai\Pages\ListDokumenPegawai;
use App\Models\DokumenPegawai;
use App\Models\JenisDokumen;
use App\Models\Prodi;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/** Daftar lintas pegawai (baca-saja); pencatatan dilakukan lewat tab Dokumen di halaman pegawai. */
class DokumenPegawaiResource extends Resource
{
    protected static ?string $model = DokumenPegawai::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string|UnitEnum|null $navigationGroup = 'Kepegawaian';

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationLabel = 'Dokumen';

    protected static ?string $modelLabel = 'dokumen';

    protected static ?string $pluralModelLabel = 'dokumen';

    /** Admin-prodi: hanya pegawai prodinya dan bukan dokumen identitas. */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with(['pegawai.prodi', 'jenisDokumen']);
        $user = auth()->user();

        if ($user && ! $user->hasAnyRole([Peran::SuperAdmin->value, Peran::AdminKepegawaian->value])) {
            $query->whereHas('jenisDokumen', fn (Builder $q) => $q->where('is_identitas', false))
                ->whereHas('pegawai', fn (Builder $q) => $user->prodi_id ? $q->where('prodi_id', $user->prodi_id) : $q->whereRaw('1 = 0'));
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
            ->defaultSort('tanggal_kedaluwarsa')
            ->columns([
                TextColumn::make('pegawai.nama_bergelar')->label('Pegawai')
                    ->searchable(query: fn (Builder $q, string $cari): Builder => $q->whereHas('pegawai', fn (Builder $p) => $p->where('nama', 'like', "%{$cari}%"))),
                TextColumn::make('pegawai.prodi.nama')->label('Prodi')->placeholder('-'),
                TextColumn::make('jenisDokumen.nama')->label('Jenis'),
                TextColumn::make('tanggal_terbit')->label('Terbit')->date('d F Y')->placeholder('-'),
                TextColumn::make('tanggal_kedaluwarsa')->label('Kedaluwarsa')->date('d F Y')->placeholder('-')->sortable(),
                TextColumn::make('status_berlaku')->label('Status')->badge(),
            ])
            ->filters([
                SelectFilter::make('status_berlaku')->label('Status')->multiple()->options(StatusBerlaku::class)
                    ->default([StatusBerlaku::SegeraBerakhir->value, StatusBerlaku::Kedaluwarsa->value]),
                SelectFilter::make('jenis_dokumen_id')->label('Jenis')->options(fn (): array => JenisDokumen::query()->orderBy('nama')->pluck('nama', 'id')->all()),
                SelectFilter::make('prodi')->label('Prodi')->options(fn (): array => Prodi::opsiAktif())
                    ->query(fn (Builder $query, array $data): Builder => $query->when($data['value'] ?? null, fn (Builder $q, $prodi) => $q->whereHas('pegawai', fn (Builder $p) => $p->where('prodi_id', $prodi)))),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ListDokumenPegawai::route('/')];
    }
}

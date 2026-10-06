<?php

namespace App\Filament\Admin\Resources\UsulanPerubahan;

use App\Enums\JenisTautan;
use App\Enums\JenisUsulan;
use App\Enums\Peran;
use App\Enums\StatusUsulan;
use App\Filament\Admin\Resources\UsulanPerubahan\Pages\ListUsulanPerubahan;
use App\Filament\Admin\Resources\UsulanPerubahan\Pages\ViewUsulanPerubahan;
use App\Filament\Forms\TautanBerkasField;
use App\Models\Prodi;
use App\Models\UsulanPerubahan;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class UsulanPerubahanResource extends Resource
{
    protected static ?string $model = UsulanPerubahan::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedInboxArrowDown;

    protected static string|UnitEnum|null $navigationGroup = 'Kepegawaian';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Usulan Perubahan';

    protected static ?string $modelLabel = 'usulan perubahan';

    protected static ?string $pluralModelLabel = 'usulan perubahan';

    /** Admin-prodi hanya usulan pegawai prodinya. */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with(['pegawai', 'pengusul']);
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

    public static function getNavigationBadge(): ?string
    {
        $jumlah = static::getEloquentQuery()->where('status', StatusUsulan::Diajukan->value)->count();

        return $jumlah > 0 ? (string) $jumlah : null;
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Usulan')->columns(2)->schema([
                TextEntry::make('pegawai.nama_bergelar')->label('Pegawai'),
                TextEntry::make('pengusul.name')->label('Pengusul'),
                TextEntry::make('jenis')->label('Jenis')->badge(),
                TextEntry::make('target')->label('Data yang diubah')->state(fn (UsulanPerubahan $record): string => $record->labelTarget()),
                TextEntry::make('status')->label('Status')->badge(),
                TextEntry::make('diajukan_at')->label('Diajukan')->dateTime('d F Y H:i')->placeholder('-'),
                TextEntry::make('alasan')->label('Alasan')->placeholder('-')->columnSpanFull(),
                TextEntry::make('catatan_verifikator')->label('Catatan verifikator')->placeholder('-')->columnSpanFull(),
                TautanBerkasField::entri('bukti_usulan', JenisTautan::BuktiUsulan, 'Berkas bukti'),
            ]),
            Section::make('Perubahan')->schema([
                ViewEntry::make('diff')->hiddenLabel()->view('filament.admin.diff-usulan')->columnSpanFull(),
            ]),
            Section::make('Riwayat status')->schema([
                RepeatableEntry::make('riwayatStatus')->hiddenLabel()->columns(4)->schema([
                    TextEntry::make('dari_status')->label('Dari')->badge()->placeholder('-'),
                    TextEntry::make('ke_status')->label('Ke')->badge(),
                    TextEntry::make('oleh.name')->label('Oleh'),
                    TextEntry::make('created_at')->label('Waktu')->dateTime('d F Y H:i'),
                    TextEntry::make('catatan')->label('Catatan')->placeholder('-')->columnSpanFull(),
                ]),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('diajukan_at', 'asc')
            ->columns([
                TextColumn::make('pegawai.nama_bergelar')->label('Pegawai')
                    ->searchable(query: fn (Builder $q, string $cari): Builder => $q->whereHas('pegawai', fn (Builder $p) => $p->where('nama', 'like', "%{$cari}%"))),
                TextColumn::make('jenis')->label('Jenis')->badge(),
                TextColumn::make('target')->label('Data yang diubah')->state(fn (UsulanPerubahan $record): string => $record->labelTarget()),
                TextColumn::make('diajukan_at')->label('Diajukan')->dateTime('d F Y H:i')->placeholder('-')->sortable(),
                TextColumn::make('umur')->label('Umur antrean')->placeholder('-')
                    ->state(fn (UsulanPerubahan $record): ?string => $record->status === StatusUsulan::Diajukan && $record->diajukan_at ? $record->diajukan_at->diffInDays(now()).' hari' : null),
                TextColumn::make('status')->label('Status')->badge(),
            ])
            ->filters([
                SelectFilter::make('status')->label('Status')->options(StatusUsulan::class)->default(StatusUsulan::Diajukan->value),
                SelectFilter::make('jenis')->label('Jenis')->options(JenisUsulan::class),
                SelectFilter::make('prodi')->label('Prodi')->options(fn (): array => Prodi::opsiAktif())
                    ->query(fn (Builder $query, array $data): Builder => $query->when($data['value'] ?? null, fn (Builder $q, $prodi) => $q->whereHas('pegawai', fn (Builder $p) => $p->where('prodi_id', $prodi)))),
            ])
            ->recordActions([ViewAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsulanPerubahan::route('/'),
            'view' => ViewUsulanPerubahan::route('/{record}'),
        ];
    }
}

<?php

namespace App\Filament\Admin\Resources\Pengingat;

use App\Actions\Pengingat\UbahStatusPengingat;
use App\Enums\JenisPengingat;
use App\Enums\Peran;
use App\Enums\StatusPengingat;
use App\Exceptions\TransisiUsulanTidakSah;
use App\Filament\Admin\Resources\Pegawai\PegawaiResource;
use App\Filament\Admin\Resources\Pengingat\Pages\ListPengingat;
use App\Models\Pengingat;
use App\Models\Prodi;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;
use UnitEnum;

class PengingatResource extends Resource
{
    protected static ?string $model = Pengingat::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBell;

    protected static string|UnitEnum|null $navigationGroup = 'Kepegawaian';

    protected static ?int $navigationSort = 4;

    protected static ?string $navigationLabel = 'Pengingat';

    protected static ?string $modelLabel = 'pengingat';

    protected static ?string $pluralModelLabel = 'pengingat';

    /** Admin-prodi hanya pengingat pegawai prodinya. */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with(['pegawai.prodi']);
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
            ->defaultSort('tanggal_jatuh_tempo')
            ->columns([
                TextColumn::make('pegawai.nama_bergelar')->label('Pegawai')
                    ->searchable(query: fn (Builder $q, string $cari): Builder => $q->whereHas('pegawai', fn (Builder $p) => $p->where('nama', 'like', "%{$cari}%"))),
                TextColumn::make('pegawai.prodi.nama')->label('Prodi')->placeholder('-'),
                TextColumn::make('jenis')->label('Jenis')->badge(),
                TextColumn::make('tanggal_jatuh_tempo')->label('Jatuh tempo')->date('d F Y')->sortable()
                    ->description(fn (Pengingat $record): string => $record->sisaHariLabel()),
                TextColumn::make('status')->label('Status')->badge(),
                TextColumn::make('tahap_terkirim')->label('Tahap terkirim')->placeholder('-')
                    ->formatStateUsing(fn ($state): string => is_array($state) ? implode(', ', array_map(fn ($t) => "H-{$t}", $state)) : '-'),
            ])
            ->filters([
                SelectFilter::make('jenis')->label('Jenis')->options(JenisPengingat::class),
                SelectFilter::make('status')->label('Status')->multiple()->options(StatusPengingat::class)
                    ->default([StatusPengingat::Aktif->value, StatusPengingat::LewatTempo->value]),
                SelectFilter::make('prodi')->label('Prodi')->options(fn (): array => Prodi::opsiAktif())
                    ->query(fn (Builder $query, array $data): Builder => $query->when($data['value'] ?? null, fn (Builder $q, $prodi) => $q->whereHas('pegawai', fn (Builder $p) => $p->where('prodi_id', $prodi)))),
                SelectFilter::make('rentang')->label('Rentang')->options(['30' => '≤ 30 hari', '90' => '≤ 90 hari', '180' => '≤ 180 hari'])
                    ->query(fn (Builder $query, array $data): Builder => $query->when($data['value'] ?? null, fn (Builder $q, $hari) => $q->whereDate('tanggal_jatuh_tempo', '<=', now()->addDays((int) $hari)->toDateString()))),
            ])
            ->recordActions([
                Action::make('tindaklanjuti')->label('Tandai ditindaklanjuti')->icon(Heroicon::OutlinedCheck)->color('warning')
                    ->schema([Textarea::make('catatan')->label('Catatan (opsional)')->maxLength(255)])
                    ->visible(fn (Pengingat $record): bool => (bool) auth()->user()?->can('update', $record) && $record->status->bolehBerpindahKe(StatusPengingat::Ditindaklanjuti))
                    ->action(fn (array $data, Pengingat $record) => self::ubah($record, StatusPengingat::Ditindaklanjuti, $data['catatan'] ?? null)),
                Action::make('abaikan')->label('Abaikan')->icon(Heroicon::OutlinedEyeSlash)->color('gray')
                    ->schema([Textarea::make('catatan')->label('Catatan')->required()->maxLength(255)])
                    ->visible(fn (Pengingat $record): bool => (bool) auth()->user()?->can('update', $record) && $record->status->bolehBerpindahKe(StatusPengingat::Diabaikan))
                    ->action(fn (array $data, Pengingat $record) => self::ubah($record, StatusPengingat::Diabaikan, $data['catatan'] ?? null)),
                Action::make('bukaPegawai')->label('Buka pegawai')->icon(Heroicon::OutlinedUser)
                    ->url(fn (Pengingat $record): string => PegawaiResource::getUrl('view', ['record' => $record->pegawai_id]))
                    ->visible(fn (Pengingat $record): bool => (bool) auth()->user()?->can('view', $record->pegawai)),
            ]);
    }

    public static function ubah(Pengingat $pengingat, StatusPengingat $ke, ?string $catatan): void
    {
        try {
            app(UbahStatusPengingat::class)->handle($pengingat, $ke, auth()->user(), $catatan);
            Notification::make()->success()->title('Status pengingat diperbarui.')->send();
        } catch (TransisiUsulanTidakSah $e) {
            Notification::make()->danger()->title($e->getMessage())->send();
        } catch (ValidationException $e) {
            Notification::make()->danger()->title(collect($e->errors())->flatten()->first())->send();
        }
    }

    public static function getPages(): array
    {
        return ['index' => ListPengingat::route('/')];
    }
}

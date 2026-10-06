<?php

namespace App\Filament\Admin\Resources\Aktivitas;

use App\Filament\Admin\Resources\Aktivitas\Pages\ListAktivitas;
use App\Filament\Admin\Resources\Aktivitas\Pages\ViewAktivitas;
use App\Models\Aktivitas;
use App\Models\User;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use UnitEnum;

class AktivitasResource extends Resource
{
    protected static ?string $model = Aktivitas::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static string|UnitEnum|null $navigationGroup = 'Sistem';

    protected static ?string $navigationLabel = 'Log Audit';

    protected static ?string $modelLabel = 'log audit';

    protected static ?string $pluralModelLabel = 'log audit';

    protected static ?string $recordTitleAttribute = 'description';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('created_at')->label('Waktu')->dateTime('d F Y H:i'),
            TextEntry::make('log_name')->label('Log')->badge(),
            TextEntry::make('description')->label('Deskripsi'),
            TextEntry::make('subject_type')->label('Objek')
                ->formatStateUsing(fn (?string $state) => $state ? class_basename($state) : '-'),
            TextEntry::make('subject_id')->label('ID objek')->placeholder('-'),
            TextEntry::make('causer.name')->label('Pelaku')->placeholder('Sistem'),
            TextEntry::make('attribute_changes')->label('Perubahan atribut')
                ->formatStateUsing(fn ($state) => self::json($state))
                ->extraAttributes(['style' => 'white-space: pre-wrap; font-family: monospace'])
                ->columnSpanFull(),
            TextEntry::make('properties')->label('Properti')
                ->formatStateUsing(fn ($state) => self::json($state))
                ->extraAttributes(['style' => 'white-space: pre-wrap; font-family: monospace'])
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')->label('Waktu')->dateTime('d F Y H:i')->sortable(),
                TextColumn::make('log_name')->label('Log')->badge(),
                TextColumn::make('description')->label('Deskripsi')->searchable(),
                TextColumn::make('subject_type')->label('Objek')
                    ->formatStateUsing(fn (?string $state) => $state ? class_basename($state) : '-'),
                TextColumn::make('subject_id')->label('ID objek')->limit(8)->placeholder('-'),
                TextColumn::make('causer.name')->label('Pelaku')->placeholder('Sistem'),
                TextColumn::make('kolom_diakses')->label('Kolom diakses')->placeholder('-')
                    ->state(fn (Aktivitas $record): ?string => $record->properties->get('kolom')),
                TextColumn::make('ip')->label('IP')->placeholder('-')
                    ->state(fn (Aktivitas $record): ?string => $record->properties->get('ip')),
            ])
            ->filters([
                Filter::make('akses_sensitif')->label('Akses data sensitif')
                    ->query(fn (Builder $query): Builder => $query->where('log_name', 'akses-sensitif')),
                SelectFilter::make('log_name')->label('Log')
                    ->options(['default' => 'default', 'akses-sensitif' => 'akses-sensitif']),
                Filter::make('rentang')->label('Rentang tanggal')
                    ->schema([
                        DatePicker::make('dari')->label('Dari'),
                        DatePicker::make('sampai')->label('Sampai'),
                    ])
                    ->query(fn (Builder $query, array $data) => $query
                        ->when($data['dari'] ?? null, fn (Builder $q, $tgl) => $q->whereDate('created_at', '>=', $tgl))
                        ->when($data['sampai'] ?? null, fn (Builder $q, $tgl) => $q->whereDate('created_at', '<=', $tgl))),
                SelectFilter::make('causer_id')->label('Pelaku')->searchable()
                    ->options(fn () => User::query()->orderBy('name')->pluck('name', 'id')->all()),
            ])
            ->recordActions([ViewAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAktivitas::route('/'),
            'view' => ViewAktivitas::route('/{record}'),
        ];
    }

    private static function json(mixed $state): string
    {
        $data = $state instanceof Collection ? $state->all() : $state;

        return json_encode($data ?: new \stdClass, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}';
    }
}

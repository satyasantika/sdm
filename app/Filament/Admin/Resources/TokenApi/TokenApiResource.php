<?php

namespace App\Filament\Admin\Resources\TokenApi;

use App\Filament\Admin\Resources\TokenApi\Pages\ListTokenApi;
use App\Models\TokenAkses;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class TokenApiResource extends Resource
{
    protected static ?string $model = TokenAkses::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedKey;

    protected static string|UnitEnum|null $navigationGroup = 'Sistem';

    protected static ?int $navigationSort = 8;

    protected static ?string $navigationLabel = 'Token API';

    protected static ?string $modelLabel = 'token API';

    protected static ?string $pluralModelLabel = 'Token API';

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('tokenable')->latest())
            ->columns([
                TextColumn::make('tokenable.name')->label('Klien')->searchable(),
                TextColumn::make('name')->label('Nama token')->searchable(),
                TextColumn::make('abilities')->label('Ability')->badge(),
                TextColumn::make('last_used_at')->label('Terakhir dipakai')->dateTime('d M Y H:i')->placeholder('Belum pernah'),
                TextColumn::make('expires_at')->label('Kedaluwarsa')->dateTime('d M Y')->placeholder('-'),
            ])
            ->recordActions([
                DeleteAction::make()->label('Cabut')->modalHeading('Cabut token ini?')
                    ->modalDescription('Klien tidak akan dapat memakai token ini lagi.')
                    ->successNotificationTitle('Token dicabut.')
                    ->after(fn (TokenAkses $record) => activity('token-api')->performedOn($record->tokenable)->causedBy(auth()->user())
                        ->withProperties(['token_id' => $record->getKey(), 'nama_token' => $record->name])->log('Token klien API dicabut')),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ListTokenApi::route('/')];
    }
}

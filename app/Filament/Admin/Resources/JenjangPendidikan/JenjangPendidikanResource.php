<?php

namespace App\Filament\Admin\Resources\JenjangPendidikan;

use App\Filament\Admin\Resources\JenjangPendidikan\Pages\CreateJenjangPendidikan;
use App\Filament\Admin\Resources\JenjangPendidikan\Pages\EditJenjangPendidikan;
use App\Filament\Admin\Resources\JenjangPendidikan\Pages\ListJenjangPendidikan;
use App\Models\JenjangPendidikan;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class JenjangPendidikanResource extends Resource
{
    protected static ?string $model = JenjangPendidikan::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBookOpen;

    protected static string|UnitEnum|null $navigationGroup = 'Master';

    protected static ?int $navigationSort = 7;

    protected static ?string $navigationLabel = 'Jenjang Pendidikan';

    protected static ?string $modelLabel = 'jenjang pendidikan';

    protected static ?string $pluralModelLabel = 'Jenjang Pendidikan';

    protected static ?string $recordTitleAttribute = 'nama';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('kode')->label('Kode')->required()->maxLength(10)->unique(ignoreRecord: true),
            TextInput::make('nama')->label('Nama')->required()->maxLength(60),
            TextInput::make('urutan')->label('Urutan')->numeric()->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('kode')->label('Kode')->searchable(),
                TextColumn::make('nama')->label('Nama')->searchable(),
                TextColumn::make('urutan')->label('Urutan')->sortable(),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
                RestoreAction::make(),
                ForceDeleteAction::make(),
            ])
            ->defaultSort('urutan');
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()->withoutGlobalScopes([SoftDeletingScope::class]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListJenjangPendidikan::route('/'),
            'create' => CreateJenjangPendidikan::route('/create'),
            'edit' => EditJenjangPendidikan::route('/{record}/edit'),
        ];
    }
}

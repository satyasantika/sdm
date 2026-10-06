<?php

namespace App\Filament\Admin\Resources\Prodi;

use App\Filament\Admin\Resources\Prodi\Pages\CreateProdi;
use App\Filament\Admin\Resources\Prodi\Pages\EditProdi;
use App\Filament\Admin\Resources\Prodi\Pages\ListProdi;
use App\Models\Prodi;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class ProdiResource extends Resource
{
    protected static ?string $model = Prodi::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAcademicCap;

    protected static string|UnitEnum|null $navigationGroup = 'Master';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Program Studi';

    protected static ?string $modelLabel = 'program studi';

    protected static ?string $pluralModelLabel = 'Program Studi';

    protected static ?string $recordTitleAttribute = 'nama';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('kode')->label('Kode')->required()->maxLength(10)->unique(ignoreRecord: true),
            TextInput::make('kode_pddikti')->label('Kode PDDIKTI')->maxLength(10)->unique(ignoreRecord: true),
            TextInput::make('nama')->label('Nama')->required()->maxLength(150),
            Select::make('jenjang')->label('Jenjang')->required()->options(['S1' => 'S1', 'S2' => 'S2', 'S3' => 'S3', 'PPG' => 'PPG', 'D3' => 'D3']),
            TextInput::make('kode_eksternal')->label('Kode eksternal')->maxLength(50),
            Toggle::make('is_aktif')->label('Aktif')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('kode')->label('Kode')->searchable()->sortable(),
                TextColumn::make('nama')->label('Nama')->searchable()->sortable(),
                TextColumn::make('jenjang')->label('Jenjang')->badge(),
                IconColumn::make('is_aktif')->label('Aktif')->boolean(),
            ])
            ->filters([
                SelectFilter::make('jenjang')->label('Jenjang')->options(['S1' => 'S1', 'S2' => 'S2', 'S3' => 'S3', 'PPG' => 'PPG', 'D3' => 'D3']),
                TernaryFilter::make('is_aktif')->label('Aktif'),
                TrashedFilter::make(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
                RestoreAction::make(),
                ForceDeleteAction::make(),
            ]);
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()->withoutGlobalScopes([SoftDeletingScope::class]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProdi::route('/'),
            'create' => CreateProdi::route('/create'),
            'edit' => EditProdi::route('/{record}/edit'),
        ];
    }
}

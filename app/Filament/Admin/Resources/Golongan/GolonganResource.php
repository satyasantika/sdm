<?php

namespace App\Filament\Admin\Resources\Golongan;

use App\Enums\JenisGolongan;
use App\Filament\Admin\Resources\Golongan\Pages\CreateGolongan;
use App\Filament\Admin\Resources\Golongan\Pages\EditGolongan;
use App\Filament\Admin\Resources\Golongan\Pages\ListGolongan;
use App\Models\Golongan;
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
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Validation\Rules\Unique;
use UnitEnum;

class GolonganResource extends Resource
{
    protected static ?string $model = Golongan::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static string|UnitEnum|null $navigationGroup = 'Master';

    protected static ?int $navigationSort = 4;

    protected static ?string $navigationLabel = 'Golongan';

    protected static ?string $modelLabel = 'golongan';

    protected static ?string $pluralModelLabel = 'Golongan';

    protected static ?string $recordTitleAttribute = 'kode';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('jenis')->label('Jenis')->options(JenisGolongan::class)->required(),
            TextInput::make('kode')->label('Kode')->required()->maxLength(10)->unique(ignoreRecord: true, modifyRuleUsing: fn (Unique $rule, Get $get) => $rule->where('jenis', $get('jenis'))),
            TextInput::make('pangkat')->label('Pangkat')->maxLength(60),
            TextInput::make('urutan')->label('Urutan')->numeric()->required(),
            Toggle::make('is_aktif')->label('Aktif')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('jenis')->label('Jenis')->badge(),
                TextColumn::make('kode')->label('Kode')->searchable(),
                TextColumn::make('pangkat')->label('Pangkat')->placeholder('-'),
                TextColumn::make('urutan')->label('Urutan')->sortable(),
                IconColumn::make('is_aktif')->label('Aktif')->boolean(),
            ])
            ->filters([
                SelectFilter::make('jenis')->label('Jenis')->options(JenisGolongan::class),
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
            'index' => ListGolongan::route('/'),
            'create' => CreateGolongan::route('/create'),
            'edit' => EditGolongan::route('/{record}/edit'),
        ];
    }
}

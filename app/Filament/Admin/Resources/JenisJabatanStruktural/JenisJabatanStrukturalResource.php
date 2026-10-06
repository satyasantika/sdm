<?php

namespace App\Filament\Admin\Resources\JenisJabatanStruktural;

use App\Filament\Admin\Resources\JenisJabatanStruktural\Pages\CreateJenisJabatanStruktural;
use App\Filament\Admin\Resources\JenisJabatanStruktural\Pages\EditJenisJabatanStruktural;
use App\Filament\Admin\Resources\JenisJabatanStruktural\Pages\ListJenisJabatanStruktural;
use App\Models\JenisJabatanStruktural;
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
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class JenisJabatanStrukturalResource extends Resource
{
    protected static ?string $model = JenisJabatanStruktural::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static string|UnitEnum|null $navigationGroup = 'Master';

    protected static ?int $navigationSort = 6;

    protected static ?string $navigationLabel = 'Jabatan Struktural';

    protected static ?string $modelLabel = 'jabatan struktural';

    protected static ?string $pluralModelLabel = 'Jabatan Struktural';

    protected static ?string $recordTitleAttribute = 'nama';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('kode')->label('Kode')->required()->maxLength(40)->unique(ignoreRecord: true),
            TextInput::make('nama')->label('Nama')->required()->maxLength(120),
            Select::make('kategori')->label('Kategori')->required()->options(['struktural' => 'Struktural', 'tugas_tambahan' => 'Tugas tambahan']),
            TextInput::make('urutan')->label('Urutan')->numeric()->default(0),
            Toggle::make('is_aktif')->label('Aktif')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nama')->label('Nama')->searchable(),
                TextColumn::make('kategori')->label('Kategori')->badge(),
                TextColumn::make('urutan')->label('Urutan')->sortable(),
                IconColumn::make('is_aktif')->label('Aktif')->boolean(),
            ])
            ->filters([
                SelectFilter::make('kategori')->label('Kategori')->options(['struktural' => 'Struktural', 'tugas_tambahan' => 'Tugas tambahan']),
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
            'index' => ListJenisJabatanStruktural::route('/'),
            'create' => CreateJenisJabatanStruktural::route('/create'),
            'edit' => EditJenisJabatanStruktural::route('/{record}/edit'),
        ];
    }
}

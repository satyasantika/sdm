<?php

namespace App\Filament\Admin\Resources\UnitKerja;

use App\Filament\Admin\Resources\UnitKerja\Pages\CreateUnitKerja;
use App\Filament\Admin\Resources\UnitKerja\Pages\EditUnitKerja;
use App\Filament\Admin\Resources\UnitKerja\Pages\ListUnitKerja;
use App\Models\Prodi;
use App\Models\UnitKerja;
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

class UnitKerjaResource extends Resource
{
    protected static ?string $model = UnitKerja::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice2;

    protected static string|UnitEnum|null $navigationGroup = 'Master';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Unit Kerja';

    protected static ?string $modelLabel = 'unit kerja';

    protected static ?string $pluralModelLabel = 'Unit Kerja';

    protected static ?string $recordTitleAttribute = 'nama';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('kode')->label('Kode')->required()->maxLength(20)->unique(ignoreRecord: true),
            TextInput::make('nama')->label('Nama')->required()->maxLength(150),
            Select::make('jenis')->label('Jenis')->required()->options(['fakultas' => 'Fakultas', 'jurusan' => 'Jurusan', 'prodi' => 'Program Studi', 'laboratorium' => 'Laboratorium', 'subbagian' => 'Subbagian', 'unit_lain' => 'Unit lain']),
            Select::make('induk_id')->label('Unit induk')->relationship('induk', 'nama')->searchable()->preload(),
            Select::make('prodi_id')->label('Program studi')->options(fn (): array => Prodi::opsiAktif())->searchable(),
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
                TextColumn::make('jenis')->label('Jenis')->badge(),
                TextColumn::make('induk.nama')->label('Induk')->placeholder('-'),
                IconColumn::make('is_aktif')->label('Aktif')->boolean(),
            ])
            ->filters([
                SelectFilter::make('jenis')->label('Jenis')->options(['fakultas' => 'Fakultas', 'jurusan' => 'Jurusan', 'prodi' => 'Program Studi', 'laboratorium' => 'Laboratorium', 'subbagian' => 'Subbagian', 'unit_lain' => 'Unit lain']),
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
            'index' => ListUnitKerja::route('/'),
            'create' => CreateUnitKerja::route('/create'),
            'edit' => EditUnitKerja::route('/{record}/edit'),
        ];
    }
}

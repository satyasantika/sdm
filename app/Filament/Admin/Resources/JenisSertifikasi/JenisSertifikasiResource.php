<?php

namespace App\Filament\Admin\Resources\JenisSertifikasi;

use App\Filament\Admin\Resources\JenisSertifikasi\Pages\CreateJenisSertifikasi;
use App\Filament\Admin\Resources\JenisSertifikasi\Pages\EditJenisSertifikasi;
use App\Filament\Admin\Resources\JenisSertifikasi\Pages\ListJenisSertifikasi;
use App\Models\JenisSertifikasi;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class JenisSertifikasiResource extends Resource
{
    protected static ?string $model = JenisSertifikasi::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTrophy;

    protected static string|UnitEnum|null $navigationGroup = 'Master';

    protected static ?int $navigationSort = 8;

    protected static ?string $navigationLabel = 'Jenis Sertifikasi';

    protected static ?string $modelLabel = 'jenis sertifikasi';

    protected static ?string $pluralModelLabel = 'Jenis Sertifikasi';

    protected static ?string $recordTitleAttribute = 'nama';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('kode')->label('Kode')->required()->maxLength(40)->unique(ignoreRecord: true),
            TextInput::make('nama')->label('Nama')->required()->maxLength(120),
            Toggle::make('is_serdos')->label('Sertifikat pendidik dosen'),
            Toggle::make('punya_masa_berlaku')->label('Punya masa berlaku'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('kode')->label('Kode')->searchable(),
                TextColumn::make('nama')->label('Nama')->searchable(),
                IconColumn::make('is_serdos')->label('Serdos')->boolean(),
                IconColumn::make('punya_masa_berlaku')->label('Masa berlaku')->boolean(),
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
            ]);
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()->withoutGlobalScopes([SoftDeletingScope::class]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListJenisSertifikasi::route('/'),
            'create' => CreateJenisSertifikasi::route('/create'),
            'edit' => EditJenisSertifikasi::route('/{record}/edit'),
        ];
    }
}

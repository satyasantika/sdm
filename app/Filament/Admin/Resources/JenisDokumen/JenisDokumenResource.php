<?php

namespace App\Filament\Admin\Resources\JenisDokumen;

use App\Filament\Admin\Resources\JenisDokumen\Pages\CreateJenisDokumen;
use App\Filament\Admin\Resources\JenisDokumen\Pages\EditJenisDokumen;
use App\Filament\Admin\Resources\JenisDokumen\Pages\ListJenisDokumen;
use App\Models\JenisDokumen;
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
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class JenisDokumenResource extends Resource
{
    protected static ?string $model = JenisDokumen::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string|UnitEnum|null $navigationGroup = 'Master';

    protected static ?int $navigationSort = 9;

    protected static ?string $navigationLabel = 'Jenis Dokumen';

    protected static ?string $modelLabel = 'jenis dokumen';

    protected static ?string $pluralModelLabel = 'Jenis Dokumen';

    protected static ?string $recordTitleAttribute = 'nama';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('kode')->label('Kode')->required()->maxLength(40)->unique(ignoreRecord: true),
            TextInput::make('nama')->label('Nama')->required()->maxLength(120),
            Toggle::make('punya_masa_berlaku')->label('Punya masa berlaku'),
            Toggle::make('is_identitas')->label('Dokumen identitas (sensitif)'),
            Select::make('wajib_untuk')->label('Wajib untuk')->placeholder('Tidak wajib')->options(['semua' => 'Semua', 'dosen' => 'Dosen', 'tendik' => 'Tendik', 'asn' => 'ASN']),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('kode')->label('Kode')->searchable(),
                TextColumn::make('nama')->label('Nama')->searchable(),
                IconColumn::make('punya_masa_berlaku')->label('Masa berlaku')->boolean(),
                IconColumn::make('is_identitas')->label('Identitas')->boolean(),
                TextColumn::make('wajib_untuk')->label('Wajib untuk')->placeholder('-'),
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
            'index' => ListJenisDokumen::route('/'),
            'create' => CreateJenisDokumen::route('/create'),
            'edit' => EditJenisDokumen::route('/{record}/edit'),
        ];
    }
}

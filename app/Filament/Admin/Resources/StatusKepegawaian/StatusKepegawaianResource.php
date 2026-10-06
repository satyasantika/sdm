<?php

namespace App\Filament\Admin\Resources\StatusKepegawaian;

use App\Enums\JenisGolongan;
use App\Enums\KelompokStatus;
use App\Filament\Admin\Resources\StatusKepegawaian\Pages\CreateStatusKepegawaian;
use App\Filament\Admin\Resources\StatusKepegawaian\Pages\EditStatusKepegawaian;
use App\Filament\Admin\Resources\StatusKepegawaian\Pages\ListStatusKepegawaian;
use App\Models\StatusKepegawaian;
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

class StatusKepegawaianResource extends Resource
{
    protected static ?string $model = StatusKepegawaian::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedIdentification;

    protected static string|UnitEnum|null $navigationGroup = 'Master';

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationLabel = 'Status Kepegawaian';

    protected static ?string $modelLabel = 'status kepegawaian';

    protected static ?string $pluralModelLabel = 'Status Kepegawaian';

    protected static ?string $recordTitleAttribute = 'nama';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('kode')->label('Kode')->required()->maxLength(30)->unique(ignoreRecord: true),
            TextInput::make('nama')->label('Nama')->required()->maxLength(100),
            Select::make('kelompok')->label('Kelompok')->options(KelompokStatus::class)->required(),
            Select::make('jenis_golongan')->label('Jenis golongan')->options(JenisGolongan::class)->placeholder('Tanpa golongan ASN'),
            Toggle::make('berlaku_kenaikan_pangkat')->label('Berlaku kenaikan pangkat')->helperText('Mengubah penanda ini memengaruhi pengingat KP/KGB dan rekap dosen tetap (BR-13, BR-14, BR-25)'),
            Toggle::make('berlaku_kgb')->label('Berlaku KGB')->helperText('Mengubah penanda ini memengaruhi pengingat KP/KGB dan rekap dosen tetap (BR-13, BR-14, BR-25)'),
            Toggle::make('dihitung_dosen_tetap')->label('Dihitung dosen tetap')->default(true)->helperText('Mengubah penanda ini memengaruhi pengingat KP/KGB dan rekap dosen tetap (BR-13, BR-14, BR-25)'),
            TextInput::make('urutan')->label('Urutan')->numeric()->default(0),
            Toggle::make('is_aktif')->label('Aktif')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('kode')->label('Kode')->searchable(),
                TextColumn::make('nama')->label('Nama')->searchable(),
                TextColumn::make('kelompok')->label('Kelompok')->badge(),
                IconColumn::make('berlaku_kenaikan_pangkat')->label('KP')->boolean(),
                IconColumn::make('berlaku_kgb')->label('KGB')->boolean(),
                IconColumn::make('dihitung_dosen_tetap')->label('Dosen tetap')->boolean(),
                IconColumn::make('is_aktif')->label('Aktif')->boolean(),
            ])
            ->filters([
                SelectFilter::make('kelompok')->label('Kelompok')->options(KelompokStatus::class),
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
            'index' => ListStatusKepegawaian::route('/'),
            'create' => CreateStatusKepegawaian::route('/create'),
            'edit' => EditStatusKepegawaian::route('/{record}/edit'),
        ];
    }
}

<?php

namespace App\Filament\Admin\Resources\JabatanFungsional;

use App\Enums\KelompokJabatan;
use App\Filament\Admin\Resources\JabatanFungsional\Pages\CreateJabatanFungsional;
use App\Filament\Admin\Resources\JabatanFungsional\Pages\EditJabatanFungsional;
use App\Filament\Admin\Resources\JabatanFungsional\Pages\ListJabatanFungsional;
use App\Models\JabatanFungsional;
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
use Filament\Schemas\Components\Section;
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

class JabatanFungsionalResource extends Resource
{
    protected static ?string $model = JabatanFungsional::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBriefcase;

    protected static string|UnitEnum|null $navigationGroup = 'Master';

    protected static ?int $navigationSort = 5;

    protected static ?string $navigationLabel = 'Jabatan Fungsional';

    protected static ?string $modelLabel = 'jabatan fungsional';

    protected static ?string $pluralModelLabel = 'Jabatan Fungsional';

    protected static ?string $recordTitleAttribute = 'nama';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('kode')->label('Kode')->required()->maxLength(40)->unique(ignoreRecord: true),
            TextInput::make('nama')->label('Nama')->required()->maxLength(100),
            Select::make('kelompok')->label('Kelompok')->options(KelompokJabatan::class)->required(),
            TextInput::make('rumpun')->label('Rumpun')->maxLength(100)->helperText('Untuk tendik, mis. Pranata Komputer atau Pustakawan'),
            TextInput::make('urutan')->label('Urutan jenjang')->numeric()->required(),
            Toggle::make('is_puncak')->label('Jenjang puncak'),
            Section::make('Syarat kenaikan (sesuai regulasi berlaku)')->description('Isi sesuai aturan jabatan fungsional terbaru; nilai ini dipakai pengingat kenaikan jabatan (BR-11, BR-15). Perlu verifikasi dengan Kemendiktisaintek.')->schema([TextInput::make('masa_kerja_minimal_bulan')->label('Masa kerja minimal (bulan)')->numeric()->minValue(0), TextInput::make('angka_kredit_minimal')->label('Angka kredit minimal')->numeric()->minValue(0), Select::make('golongan_minimal_id')->label('Golongan minimal')->relationship('golonganMinimal', 'kode')->searchable()->preload()])->columns(2)->columnSpanFull(),
            TextInput::make('dasar_hukum')->label('Dasar hukum')->maxLength(255)->columnSpanFull(),
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
                TextColumn::make('rumpun')->label('Rumpun')->placeholder('-'),
                TextColumn::make('urutan')->label('Urutan')->sortable(),
                TextColumn::make('masa_kerja_minimal_bulan')->label('Masa kerja min. (bln)')->placeholder('-'),
                TextColumn::make('angka_kredit_minimal')->label('Angka kredit min.')->placeholder('-'),
                TextColumn::make('golonganMinimal.kode')->label('Golongan min.')->placeholder('-'),
                IconColumn::make('is_puncak')->label('Puncak')->boolean(),
            ])
            ->filters([
                SelectFilter::make('kelompok')->label('Kelompok')->options(KelompokJabatan::class),
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
            'index' => ListJabatanFungsional::route('/'),
            'create' => CreateJabatanFungsional::route('/create'),
            'edit' => EditJabatanFungsional::route('/{record}/edit'),
        ];
    }
}

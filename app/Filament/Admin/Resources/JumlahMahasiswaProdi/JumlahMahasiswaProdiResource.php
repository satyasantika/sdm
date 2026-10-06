<?php

namespace App\Filament\Admin\Resources\JumlahMahasiswaProdi;

use App\Filament\Admin\Resources\JumlahMahasiswaProdi\Pages\CreateJumlahMahasiswaProdi;
use App\Filament\Admin\Resources\JumlahMahasiswaProdi\Pages\EditJumlahMahasiswaProdi;
use App\Filament\Admin\Resources\JumlahMahasiswaProdi\Pages\ListJumlahMahasiswaProdi;
use App\Models\JumlahMahasiswaProdi;
use App\Models\Prodi;
use App\Models\Semester;
use App\Support\CakupanProdi;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rules\Unique;
use UnitEnum;

class JumlahMahasiswaProdiResource extends Resource
{
    protected static ?string $model = JumlahMahasiswaProdi::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static string|UnitEnum|null $navigationGroup = 'BKD';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Jumlah Mahasiswa';

    protected static ?string $modelLabel = 'jumlah mahasiswa';

    protected static ?string $pluralModelLabel = 'jumlah mahasiswa';

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with(['prodi', 'semester']);

        if ($prodi = CakupanProdi::terkunci(auth()->user())) {
            $query->where('prodi_id', $prodi);
        }

        return $query;
    }

    public static function form(Schema $schema): Schema
    {
        $terkunci = CakupanProdi::terkunci(auth()->user());

        return $schema->components([
            Select::make('prodi_id')->label('Program studi')->required()->searchable()
                ->options(fn (): array => Prodi::opsiAktif())
                ->default($terkunci)->disabled($terkunci !== null)->dehydrated(),
            Select::make('semester_id')->label('Semester')->required()
                ->options(fn (): array => Semester::query()->orderByDesc('kode')->get()->mapWithKeys(fn (Semester $s): array => [$s->id => $s->label])->all())
                ->default(fn (): ?string => Semester::aktifSekarang()?->id)
                ->unique(ignoreRecord: true, modifyRuleUsing: fn (Unique $rule, Get $get) => $rule->where('prodi_id', $get('prodi_id')))
                ->validationMessages(['unique' => 'Jumlah mahasiswa prodi ini untuk semester tersebut sudah ada.']),
            TextInput::make('jumlah_mahasiswa_aktif')->label('Jumlah mahasiswa aktif')->required()->numeric()->integer()->minValue(0),
            TextInput::make('sumber')->label('Sumber data')->required()->maxLength(100)->placeholder('PDDIKTI per 31 Oktober'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('prodi.nama')->label('Program studi')->sortable(),
                TextColumn::make('semester.label')->label('Semester'),
                TextColumn::make('jumlah_mahasiswa_aktif')->label('Mahasiswa aktif')->numeric(),
                TextColumn::make('sumber')->label('Sumber')->placeholder('-'),
            ])
            ->recordActions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListJumlahMahasiswaProdi::route('/'),
            'create' => CreateJumlahMahasiswaProdi::route('/create'),
            'edit' => EditJumlahMahasiswaProdi::route('/{record}/edit'),
        ];
    }
}

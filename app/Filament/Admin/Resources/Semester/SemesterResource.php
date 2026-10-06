<?php

namespace App\Filament\Admin\Resources\Semester;

use App\Filament\Admin\Resources\Semester\Pages\CreateSemester;
use App\Filament\Admin\Resources\Semester\Pages\EditSemester;
use App\Filament\Admin\Resources\Semester\Pages\ListSemester;
use App\Models\Semester;
use BackedEnum;
use Closure;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class SemesterResource extends Resource
{
    protected static ?string $model = Semester::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static string|UnitEnum|null $navigationGroup = 'Master';

    protected static ?int $navigationSort = 10;

    protected static ?string $navigationLabel = 'Semester';

    protected static ?string $modelLabel = 'semester';

    protected static ?string $pluralModelLabel = 'Semester';

    protected static ?string $recordTitleAttribute = 'kode';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('kode')->label('Kode')->required()->length(5)->regex('/^\d{4}[12]$/')->unique(ignoreRecord: true)->helperText('Pola PDDIKTI: 20251 = 2025/2026 Ganjil, 20252 = Genap')->validationMessages(['regex' => 'Kode harus berpola tahun + 1 (ganjil) atau 2 (genap), mis. 20251.']),
            TextInput::make('tahun_akademik')->label('Tahun akademik')->required()->maxLength(9)->placeholder('2025/2026')->rules([fn (Get $get): Closure => function (string $attribute, mixed $value, Closure $fail) use ($get): void {
                $kode = (string) $get('kode');
                if (preg_match('/^(\d{4})([12])$/', $kode, $m) && $value !== $m[1].'/'.($m[1] + 1)) {
                    $fail('Tahun akademik tidak sesuai dengan kode semester.');
                }
            }]),
            Select::make('jenis')->label('Jenis')->required()->options(['ganjil' => 'Ganjil', 'genap' => 'Genap'])->rules([fn (Get $get): Closure => function (string $attribute, mixed $value, Closure $fail) use ($get): void {
                $kode = (string) $get('kode');
                if (preg_match('/^\d{4}([12])$/', $kode, $m) && $value !== ($m[1] === '1' ? 'ganjil' : 'genap')) {
                    $fail('Jenis semester tidak sesuai dengan kode semester.');
                }
            }]),
            DatePicker::make('tanggal_mulai')->label('Tanggal mulai'),
            DatePicker::make('tanggal_selesai')->label('Tanggal selesai')->afterOrEqual('tanggal_mulai'),
            Toggle::make('is_aktif')->label('Aktif'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('kode')->label('Kode')->searchable()->sortable(),
                TextColumn::make('label')->label('Semester'),
                IconColumn::make('is_aktif')->label('Aktif')->boolean(),
            ])
            ->filters([])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                Action::make('aktifkan')->label('Jadikan aktif')->icon(Heroicon::OutlinedCheckCircle)->requiresConfirmation()->visible(fn (Semester $record): bool => ! $record->is_aktif && auth()->user()->can('update', $record))->action(fn (Semester $record) => $record->aktifkan()),
                DeleteAction::make(),
            ])
            ->defaultSort('kode', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSemester::route('/'),
            'create' => CreateSemester::route('/create'),
            'edit' => EditSemester::route('/{record}/edit'),
        ];
    }
}

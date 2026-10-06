<?php

namespace App\Filament\Admin\Resources\Bkd;

use App\Enums\KesimpulanBkd;
use App\Enums\Peran;
use App\Filament\Admin\Resources\Bkd\Pages\CreateBkd;
use App\Filament\Admin\Resources\Bkd\Pages\EditBkd;
use App\Filament\Admin\Resources\Bkd\Pages\ListBkd;
use App\Models\Bkd;
use App\Models\JabatanFungsional;
use App\Models\Pegawai;
use App\Models\Prodi;
use App\Models\Semester;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rules\Unique;
use UnitEnum;

class BkdResource extends Resource
{
    protected static ?string $model = Bkd::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBarSquare;

    protected static string|UnitEnum|null $navigationGroup = 'BKD';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Rekap BKD';

    protected static ?string $modelLabel = 'BKD';

    protected static ?string $pluralModelLabel = 'BKD';

    /** Admin-prodi hanya BKD dosen prodinya. */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with(['pegawai.prodi', 'pegawai.jabatanFungsional', 'semester']);
        $user = auth()->user();

        if ($user && $user->hasRole(Peran::AdminProdi->value) && ! $user->hasAnyRole([Peran::SuperAdmin->value, Peran::AdminKepegawaian->value])) {
            $query->whereHas('pegawai', fn (Builder $q) => $user->prodi_id ? $q->where('prodi_id', $user->prodi_id) : $q->whereRaw('1 = 0'));
        }

        return $query;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('pegawai_id')->label('Dosen')->required()->searchable()
                ->options(fn (): array => Pegawai::query()->dosen()->orderBy('nama')->get()->mapWithKeys(fn (Pegawai $p): array => [$p->id => $p->nama_bergelar.' ('.($p->nidn ?? '-').')'])->all()),
            Select::make('semester_id')->label('Semester')->required()
                ->options(fn (): array => Semester::query()->orderByDesc('kode')->get()->mapWithKeys(fn (Semester $s): array => [$s->id => $s->label])->all())
                ->default(fn (): ?string => Semester::aktifSekarang()?->id)
                ->unique(ignoreRecord: true, modifyRuleUsing: fn (Unique $rule, $get) => $rule->where('pegawai_id', $get('pegawai_id')))
                ->validationMessages(['unique' => 'BKD dosen ini untuk semester tersebut sudah ada.']),
            TextInput::make('sks_pendidikan')->label('SKS pendidikan')->numeric()->minValue(0)->step('0.01'),
            TextInput::make('sks_penelitian')->label('SKS penelitian')->numeric()->minValue(0)->step('0.01'),
            TextInput::make('sks_pengabdian')->label('SKS pengabdian')->numeric()->minValue(0)->step('0.01'),
            TextInput::make('sks_penunjang')->label('SKS penunjang')->numeric()->minValue(0)->step('0.01'),
            TextInput::make('total_sks')->label('Total SKS')->numeric()->minValue(0)->step('0.01'),
            Select::make('kesimpulan')->label('Kesimpulan')->options(KesimpulanBkd::class)->required()->default(KesimpulanBkd::BelumDinilai->value),
            TextInput::make('kewajiban_khusus')->label('Kewajiban khusus')->maxLength(30),
            Textarea::make('catatan')->label('Catatan koreksi')->maxLength(255)->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('pegawai.nama_bergelar')->label('Dosen')
                    ->searchable(query: fn (Builder $q, string $cari): Builder => $q->whereHas('pegawai', fn (Builder $p) => $p->where('nama', 'like', "%{$cari}%"))),
                TextColumn::make('pegawai.nidn')->label('NIDN')->placeholder('-'),
                TextColumn::make('pegawai.prodi.nama')->label('Prodi')->placeholder('-'),
                TextColumn::make('semester.label')->label('Semester'),
                TextColumn::make('sks_pendidikan')->label('Pendidikan')->placeholder('-'),
                TextColumn::make('sks_penelitian')->label('Penelitian')->placeholder('-'),
                TextColumn::make('sks_pengabdian')->label('Pengabdian')->placeholder('-'),
                TextColumn::make('sks_penunjang')->label('Penunjang')->placeholder('-'),
                TextColumn::make('total_sks')->label('Total')->placeholder('-'),
                TextColumn::make('kesimpulan')->label('Kesimpulan')->badge(),
                TextColumn::make('sumber')->label('Sumber'),
            ])
            ->filters([
                SelectFilter::make('semester_id')->label('Semester')
                    ->options(fn (): array => Semester::query()->orderByDesc('kode')->get()->mapWithKeys(fn (Semester $s): array => [$s->id => $s->label])->all())
                    ->default(fn (): ?string => Semester::aktifSekarang()?->id),
                SelectFilter::make('prodi')->label('Prodi')->options(fn (): array => Prodi::opsiAktif())
                    ->query(fn (Builder $query, array $data): Builder => $query->when($data['value'] ?? null, fn (Builder $q, $prodi) => $q->whereHas('pegawai', fn (Builder $p) => $p->where('prodi_id', $prodi)))),
                SelectFilter::make('kesimpulan')->label('Kesimpulan')->options(KesimpulanBkd::class),
                SelectFilter::make('jabatan_fungsional')->label('Jabatan fungsional')->options(fn (): array => JabatanFungsional::opsiAktif())
                    ->query(fn (Builder $query, array $data): Builder => $query->when($data['value'] ?? null, fn (Builder $q, $jabatan) => $q->whereHas('pegawai', fn (Builder $p) => $p->where('jabatan_fungsional_id', $jabatan)))),
            ])
            ->recordActions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBkd::route('/'),
            'create' => CreateBkd::route('/create'),
            'edit' => EditBkd::route('/{record}/edit'),
        ];
    }
}

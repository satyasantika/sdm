<?php

namespace App\Filament\Admin\Resources\Pegawai;

use App\Actions\Pegawai\BuatAkunPegawai;
use App\Actions\Pegawai\TampilkanDataSensitif;
use App\Enums\JenisKelamin;
use App\Enums\JenisPegawai;
use App\Enums\Peran;
use App\Enums\StatusAktifPegawai;
use App\Filament\Admin\Resources\Pegawai\Pages\CreatePegawai;
use App\Filament\Admin\Resources\Pegawai\Pages\EditPegawai;
use App\Filament\Admin\Resources\Pegawai\Pages\ListPegawai;
use App\Filament\Admin\Resources\Pegawai\Pages\ViewPegawai;
use App\Models\JabatanFungsional;
use App\Models\Pegawai;
use App\Models\Prodi;
use App\Models\StatusKepegawaian;
use App\Models\UnitKerja;
use App\Rules\NikBelumTerdaftar;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use UnitEnum;

class PegawaiResource extends Resource
{
    protected static ?string $model = Pegawai::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static string|UnitEnum|null $navigationGroup = 'Kepegawaian';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Pegawai';

    protected static ?string $modelLabel = 'pegawai';

    protected static ?string $pluralModelLabel = 'pegawai';

    protected static ?string $recordTitleAttribute = 'nama';

    /** BR-03: admin-prodi hanya melihat pegawai prodinya. */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->withoutGlobalScopes([SoftDeletingScope::class]);
        $user = auth()->user();

        if ($user && $user->hasRole(Peran::AdminProdi->value) && ! $user->hasAnyRole([Peran::SuperAdmin->value, Peran::AdminKepegawaian->value])) {
            $user->prodi_id ? $query->where('prodi_id', $user->prodi_id) : $query->whereRaw('1 = 0');
        }

        return $query;
    }

    public static function form(Schema $schema): Schema
    {
        $jenis = fn (Get $get): ?JenisPegawai => self::jenisDari($get('jenis_pegawai'));
        $tulisSaja = fn (string $kolom) => fn (?Pegawai $record): string => $record?->getRawOriginal($kolom) ? 'Terisi — kosongkan bila tidak diubah' : '';

        return $schema->components([
            Section::make('Identitas')->columns(2)->schema([
                Select::make('jenis_pegawai')->label('Jenis pegawai')->options(JenisPegawai::class)->required()->live()
                    ->default(fn (): string => self::adalahAdminProdi() ? JenisPegawai::Dosen->value : JenisPegawai::Dosen->value)
                    ->disabled(fn (): bool => self::adalahAdminProdi()),
                TextInput::make('gelar_depan')->label('Gelar depan')->maxLength(50),
                TextInput::make('nama')->label('Nama (tanpa gelar)')->required()->maxLength(150),
                TextInput::make('gelar_belakang')->label('Gelar belakang')->maxLength(80),
                TextInput::make('nip')->label('NIP')->regex('/^\d{18}$/')->unique(ignoreRecord: true)
                    ->validationMessages(['regex' => 'NIP harus 18 digit angka.']),
                TextInput::make('nidn')->label('NIDN')->regex('/^\d{10}$/')->unique(ignoreRecord: true)
                    ->validationMessages(['regex' => 'NIDN harus 10 digit angka.']),
                TextInput::make('nidk')->label('NIDK')->regex('/^\d{10}$/')->unique(ignoreRecord: true)
                    ->validationMessages(['regex' => 'NIDK harus 10 digit angka.']),
                TextInput::make('nuptk')->label('NUPTK')->regex('/^\d{16}$/')->unique(ignoreRecord: true)
                    ->validationMessages(['regex' => 'NUPTK harus 16 digit angka.']),
                TextInput::make('nik')->label('NIK')->regex('/^\d{16}$/')
                    ->placeholder($tulisSaja('nik'))
                    ->rule(fn (?Pegawai $record): NikBelumTerdaftar => new NikBelumTerdaftar($record?->getKey()))
                    ->afterStateHydrated(fn (TextInput $component) => $component->state(null))
                    ->dehydrated(fn (?string $state): bool => filled($state))
                    ->validationMessages(['regex' => 'NIK harus 16 digit angka.']),
            ]),
            Section::make('Data pribadi')->columns(2)->schema([
                TextInput::make('tempat_lahir')->label('Tempat lahir')->maxLength(80),
                DatePicker::make('tanggal_lahir')->label('Tanggal lahir'),
                Select::make('jenis_kelamin')->label('Jenis kelamin')->options(JenisKelamin::class),
                Select::make('agama')->label('Agama')->options(array_combine(self::AGAMA, self::AGAMA)),
                Select::make('status_perkawinan')->label('Status perkawinan')->options([
                    'belum_kawin' => 'Belum kawin', 'kawin' => 'Kawin', 'cerai_hidup' => 'Cerai hidup', 'cerai_mati' => 'Cerai mati',
                ]),
                TextInput::make('no_hp')->label('No. HP')->maxLength(20),
                TextInput::make('email_pribadi')->label('Surel pribadi')->email()->maxLength(150),
                TextInput::make('email_unsil')->label('Surel Unsil')->email()->maxLength(150)->unique(ignoreRecord: true),
                Textarea::make('alamat')->label('Alamat')->columnSpanFull(),
            ]),
            Section::make('Kepegawaian')->columns(2)->schema([
                Select::make('status_kepegawaian_id')->label('Status kepegawaian')->required()
                    ->options(fn (): array => StatusKepegawaian::opsiAktif())->searchable(),
                Select::make('prodi_id')->label('Prodi homebase')->searchable()
                    ->options(fn (): array => Prodi::opsiAktif())
                    ->required(fn (Get $get): bool => $jenis($get) === JenisPegawai::Dosen)
                    ->default(fn (): ?string => self::adalahAdminProdi() ? auth()->user()->prodi_id : null)
                    ->disabled(fn (): bool => self::adalahAdminProdi())
                    ->dehydratedWhenHidden()
                    ->validationMessages(['required' => 'Prodi homebase wajib diisi untuk dosen.']),
                Select::make('unit_kerja_id')->label('Unit kerja')->searchable()
                    ->options(fn (): array => UnitKerja::query()->where('is_aktif', true)->orderBy('nama')->pluck('nama', 'id')->all())
                    ->required(fn (Get $get): bool => $jenis($get) === JenisPegawai::Tendik)
                    ->validationMessages(['required' => 'Unit kerja wajib diisi untuk tendik.']),
                DatePicker::make('tmt_cpns')->label('TMT CPNS'),
                DatePicker::make('tmt_pns')->label('TMT PNS'),
                DatePicker::make('tmt_masuk')->label('TMT masuk Unsil'),
                TextInput::make('kode_eksternal')->label('Kode eksternal')->maxLength(50),
            ]),
            Section::make('Keuangan')->columns(2)->schema([
                TextInput::make('npwp')->label('NPWP')->maxLength(30)
                    ->placeholder($tulisSaja('npwp'))
                    ->afterStateHydrated(fn (TextInput $component) => $component->state(null))
                    ->dehydrated(fn (?string $state): bool => filled($state)),
                TextInput::make('nama_bank')->label('Nama bank')->maxLength(60),
                TextInput::make('nomor_rekening')->label('Nomor rekening')->maxLength(30)
                    ->placeholder($tulisSaja('nomor_rekening'))
                    ->afterStateHydrated(fn (TextInput $component) => $component->state(null))
                    ->dehydrated(fn (?string $state): bool => filled($state)),
            ]),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make('Pegawai')->columnSpanFull()->tabs([
                Tab::make('Biodata')->columns(2)->schema([
                    TextEntry::make('nama_bergelar')->label('Nama'),
                    TextEntry::make('jenis_pegawai')->label('Jenis')->badge(),
                    TextEntry::make('nip')->label('NIP')->placeholder('-'),
                    TextEntry::make('nidn')->label('NIDN')->placeholder('-'),
                    TextEntry::make('nuptk')->label('NUPTK')->placeholder('-'),
                    TextEntry::make('jenis_kelamin')->label('Jenis kelamin')->placeholder('-'),
                    TextEntry::make('tempat_lahir')->label('Tempat lahir')->placeholder('-'),
                    TextEntry::make('email_unsil')->label('Surel Unsil')->placeholder('-'),
                    TextEntry::make('no_hp')->label('No. HP')->placeholder('-'),
                    TextEntry::make('prodi.nama')->label('Prodi homebase')->placeholder('-'),
                    TextEntry::make('unitKerja.nama')->label('Unit kerja')->placeholder('-'),
                    TextEntry::make('statusKepegawaian.nama')->label('Status kepegawaian'),
                    TextEntry::make('jabatanFungsional.nama')->label('Jabatan fungsional')->placeholder('-'),
                    TextEntry::make('golongan.label')->label('Golongan')->placeholder('-'),
                    TextEntry::make('status_aktif')->label('Status keaktifan')->badge(),
                    TextEntry::make('tanggal_pensiun')->label('Tanggal pensiun')->date('d F Y')->placeholder('-'),
                    TextEntry::make('tmt_masuk')->label('TMT masuk')->date('d F Y')->placeholder('-'),
                ]),
                Tab::make('Data pribadi')->columns(2)->schema([
                    TextEntry::make('tahun_lahir')->label('Tahun lahir')->state(fn (Pegawai $record): ?int => $record->tanggal_lahir?->year)
                        ->visible(fn (): bool => ! self::bolehLihatSensitif())->placeholder('-'),
                    TextEntry::make('tanggal_lahir')->label('Tanggal lahir')->date('d F Y')->placeholder('-')
                        ->visible(fn (): bool => self::bolehLihatSensitif()),
                    TextEntry::make('nik_tersamar')->label('NIK')->fontFamily('mono')
                        ->suffixAction(self::aksiTampil('nik', 'NIK')),
                    TextEntry::make('npwp_tersamar')->label('NPWP')->fontFamily('mono')
                        ->suffixAction(self::aksiTampil('npwp', 'NPWP')),
                    TextEntry::make('rekening_tersamar')->label('Nomor rekening')->fontFamily('mono')
                        ->suffixAction(self::aksiTampil('nomor_rekening', 'Nomor rekening')),
                    TextEntry::make('nama_bank')->label('Bank')->placeholder('-'),
                ])->visible(fn (): bool => self::bolehLihatSensitif() || auth()->user()?->can('pegawai.lihat')),
                Tab::make('Alamat & keluarga')->schema([
                    TextEntry::make('alamat')->label('Alamat')->placeholder('-'),
                ])->visible(fn (): bool => self::bolehLihatSensitif()),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['prodi', 'statusKepegawaian', 'jabatanFungsional', 'golongan']))
            ->defaultSort('nama')
            ->columns([
                TextColumn::make('nama_bergelar')->label('Nama')
                    ->searchable(query: fn (Builder $query, string $search): Builder => $query->orWhere(function (Builder $q) use ($search): void {
                        DB::getDriverName() === 'mysql' ? $q->whereFullText('nama', $search)->orWhere('nama', 'like', "%{$search}%") : $q->where('nama', 'like', "%{$search}%");
                    }))
                    ->sortable(query: fn (Builder $query, string $direction): Builder => $query->orderBy('nama', $direction)),
                TextColumn::make('nip')->label('NIP')->searchable()->placeholder('-'),
                TextColumn::make('nidn')->label('NIDN')->searchable()->placeholder('-'),
                TextColumn::make('nuptk')->label('NUPTK')->searchable()->placeholder('-')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('jenis_pegawai')->label('Jenis')->badge(),
                TextColumn::make('prodi.nama')->label('Prodi')->placeholder('-'),
                TextColumn::make('statusKepegawaian.nama')->label('Status')->placeholder('-'),
                TextColumn::make('jabatanFungsional.nama')->label('Jabatan fungsional')->placeholder('-'),
                TextColumn::make('golongan.kode')->label('Golongan')->placeholder('-'),
                TextColumn::make('status_aktif')->label('Keaktifan')->badge(),
                TextColumn::make('tanggal_pensiun')->label('Pensiun')->date('d F Y')->placeholder('-')->sortable(),
            ])
            ->filters([
                SelectFilter::make('jenis_pegawai')->label('Jenis')->options(JenisPegawai::class),
                SelectFilter::make('prodi_id')->label('Prodi')->options(fn (): array => Prodi::opsiAktif()),
                SelectFilter::make('status_kepegawaian_id')->label('Status kepegawaian')->options(fn (): array => StatusKepegawaian::opsiAktif()),
                SelectFilter::make('jabatan_fungsional_id')->label('Jabatan fungsional')->options(fn (): array => JabatanFungsional::opsiAktif()),
                SelectFilter::make('status_aktif')->label('Keaktifan')->multiple()->options(StatusAktifPegawai::class)
                    ->default([StatusAktifPegawai::Aktif->value, StatusAktifPegawai::TugasBelajar->value]),
                Filter::make('pensiun_5_tahun')->label('Pensiun ≤ 5 tahun')
                    ->query(fn (Builder $query): Builder => $query->whereBetween('tanggal_pensiun', [now()->toDateString(), now()->addYears(5)->toDateString()])),
            ])
            ->recordActions([ViewAction::make(), EditAction::make()])
            ->toolbarActions([
                BulkAction::make('buatAkun')->label('Buat akun swalayan')->icon(Heroicon::OutlinedUserPlus)
                    ->requiresConfirmation()
                    ->visible(fn (): bool => (bool) auth()->user()?->canAny(['pengguna.kelola', 'pegawai.impor']))
                    ->deselectRecordsAfterCompletion()
                    ->action(function (Collection $records): void {
                        $berhasil = 0;
                        $gagal = [];
                        foreach ($records as $pegawai) {
                            try {
                                app(BuatAkunPegawai::class)->handle($pegawai);
                                $berhasil++;
                            } catch (ValidationException $e) {
                                $gagal[] = collect($e->errors())->flatten()->first();
                            }
                        }

                        Notification::make()
                            ->title("{$berhasil} akun dibuat".($gagal ? ', '.count($gagal).' dilewati' : ''))
                            ->body($gagal ? implode("\n", $gagal) : null)
                            ->color($gagal ? 'warning' : 'success')
                            ->send();
                    }),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\JabatanFungsionalRelationManager::class,
            RelationManagers\PangkatRelationManager::class,
            RelationManagers\KgbRelationManager::class,
            RelationManagers\StrukturalRelationManager::class,
            RelationManagers\PendidikanRelationManager::class,
            RelationManagers\SertifikasiRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPegawai::route('/'),
            'create' => CreatePegawai::route('/create'),
            'view' => ViewPegawai::route('/{record}'),
            'edit' => EditPegawai::route('/{record}/edit'),
        ];
    }

    private static function bolehLihatSensitif(): bool
    {
        return (bool) auth()->user()?->can('pegawai.lihat-sensitif');
    }

    private static function aksiTampil(string $kolom, string $label): Action
    {
        return Action::make('tampil_'.$kolom)
            ->icon(Heroicon::OutlinedEye)
            ->tooltip('Tampilkan '.$label)
            ->visible(fn (Pegawai $record): bool => Gate::allows('viewSensitive', $record))
            ->modalHeading('Tampilkan '.$label)
            ->modalDescription('Akses ini dicatat di log audit.')
            ->fillForm(fn (Pegawai $record): array => [
                'nilai' => app(TampilkanDataSensitif::class)->handle(auth()->user(), $record, $kolom),
            ])
            ->schema([TextInput::make('nilai')->label($label)->readOnly()])
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Tutup');
    }

    public static function adalahAdminProdi(): bool
    {
        $user = auth()->user();

        return $user !== null
            && $user->hasRole(Peran::AdminProdi->value)
            && ! $user->hasAnyRole([Peran::SuperAdmin->value, Peran::AdminKepegawaian->value]);
    }

    private static function jenisDari(mixed $nilai): ?JenisPegawai
    {
        return $nilai instanceof JenisPegawai ? $nilai : (is_string($nilai) ? JenisPegawai::tryFrom($nilai) : null);
    }

    private const AGAMA = ['Islam', 'Kristen', 'Katolik', 'Hindu', 'Buddha', 'Konghucu'];
}

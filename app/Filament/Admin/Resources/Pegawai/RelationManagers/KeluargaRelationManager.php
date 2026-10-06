<?php

namespace App\Filament\Admin\Resources\Pegawai\RelationManagers;

use App\Actions\Riwayat\SimpanKeluarga;
use App\Enums\HubunganKeluarga;
use App\Enums\JenisKelamin;
use App\Models\Keluarga;
use App\Models\Pegawai;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class KeluargaRelationManager extends RelationManager
{
    protected static string $relationship = 'keluarga';

    protected static ?string $title = 'Keluarga';

    protected static ?string $modelLabel = 'anggota keluarga';

    /** Hanya admin-kepegawaian dan super-admin (keluarga.lihat). */
    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return (bool) auth()->user()?->can('keluarga.lihat');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('hubungan')->label('Hubungan')->options(HubunganKeluarga::class)->required()->live(),
            TextInput::make('nama')->label('Nama')->required()->maxLength(150),
            TextInput::make('nik')->label('NIK')->regex('/^\d{16}$/')
                ->placeholder(fn (?Keluarga $record): string => $record?->getRawOriginal('nik') ? 'Terisi — kosongkan bila tidak diubah' : '')
                ->afterStateHydrated(fn (TextInput $component) => $component->state(null))
                ->dehydrated(fn (?string $state): bool => filled($state))
                ->validationMessages(['regex' => 'NIK harus 16 digit angka.']),
            TextInput::make('tempat_lahir')->label('Tempat lahir')->maxLength(80),
            DatePicker::make('tanggal_lahir')->label('Tanggal lahir'),
            Select::make('jenis_kelamin')->label('Jenis kelamin')->options(JenisKelamin::class),
            TextInput::make('pekerjaan')->label('Pekerjaan')->maxLength(100),
            Toggle::make('status_tunjangan')->label('Masuk daftar tunjangan'),
            DatePicker::make('tanggal_nikah')->label('Tanggal nikah')
                ->visible(fn ($get): bool => in_array($get('hubungan'), ['suami', 'istri'], true)),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('hubungan')->label('Hubungan')->badge(),
                TextColumn::make('nama')->label('Nama'),
                TextColumn::make('nik_tersamar')->label('NIK')->fontFamily('mono'),
                TextColumn::make('tanggal_lahir')->label('Tanggal lahir')->date('d F Y')->placeholder('-'),
                TextColumn::make('pekerjaan')->label('Pekerjaan')->placeholder('-'),
                IconColumn::make('status_tunjangan')->label('Tunjangan')->boolean(),
            ])
            ->headerActions([
                CreateAction::make()->label('Tambah anggota')->using(fn (array $data): Model => $this->simpan($data)),
            ])
            ->recordActions([
                EditAction::make()->using(fn (Model $record, array $data): Model => $this->simpan($data, $record)),
                DeleteAction::make(),
            ]);
    }

    /** @param  array<string, mixed>  $data */
    private function simpan(array $data, ?Model $record = null): Model
    {
        /** @var Pegawai $pegawai */
        $pegawai = $this->getOwnerRecord();
        $aksi = app(SimpanKeluarga::class);
        /** @var Keluarga|null $record */
        $keluarga = $aksi->handle($pegawai, $data, $record);

        if ($aksi->adaPasanganGanda($pegawai)) {
            Notification::make()->warning()->title('Lebih dari satu pasangan tercatat')
                ->body('Periksa kembali data suami/istri pegawai ini.')->send();
        }

        return $keluarga;
    }
}

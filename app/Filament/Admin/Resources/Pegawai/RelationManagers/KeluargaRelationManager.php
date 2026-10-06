<?php

namespace App\Filament\Admin\Resources\Pegawai\RelationManagers;

use App\Actions\Riwayat\SimpanKeluarga;
use App\Filament\Schemas\KeluargaForm;
use App\Models\Keluarga;
use App\Models\Pegawai;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
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
        /** @var Pegawai $pegawai */
        $pegawai = $this->getOwnerRecord();

        return $schema->components(KeluargaForm::components($pegawai));
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

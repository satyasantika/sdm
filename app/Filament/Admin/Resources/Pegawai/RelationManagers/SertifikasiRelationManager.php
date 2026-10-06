<?php

namespace App\Filament\Admin\Resources\Pegawai\RelationManagers;

use App\Actions\Riwayat\SimpanSertifikasi;
use App\Enums\JenisTautan;
use App\Enums\StatusBerlaku;
use App\Filament\Forms\TautanBerkasField;
use App\Models\JenisSertifikasi;
use App\Models\Pegawai;
use App\Models\Sertifikasi;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class SertifikasiRelationManager extends RelationManager
{
    protected static string $relationship = 'sertifikasi';

    protected static ?string $title = 'Sertifikasi';

    protected static ?string $modelLabel = 'sertifikasi';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('jenis_sertifikasi_id')->label('Jenis')->required()->live()
                ->options(fn (): array => JenisSertifikasi::query()->orderBy('nama')->pluck('nama', 'id')->all()),
            TextInput::make('nama')->label('Nama sertifikat')->required()->maxLength(200),
            TextInput::make('nomor_sertifikat')->label('Nomor sertifikat')->maxLength(100),
            TextInput::make('nomor_registrasi')->label('Nomor registrasi')->maxLength(100),
            TextInput::make('bidang')->label('Bidang')->maxLength(150),
            TextInput::make('penerbit')->label('Penerbit')->maxLength(150),
            TextInput::make('tahun')->label('Tahun')->numeric()->minValue(1950)->maxValue((int) now()->year),
            DatePicker::make('tanggal_terbit')->label('Tanggal terbit'),
            DatePicker::make('tanggal_kedaluwarsa')->label('Tanggal kedaluwarsa')
                ->required(fn (Get $get): bool => (bool) JenisSertifikasi::whereKey($get('jenis_sertifikasi_id'))->value('punya_masa_berlaku')),
            TautanBerkasField::make('sertifikat', JenisTautan::Sertifikat, 'Tautan sertifikat')->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['jenisSertifikasi', 'tautanBerkas']))
            ->defaultSort('tanggal_terbit', 'desc')
            ->columns([
                TextColumn::make('jenisSertifikasi.nama')->label('Jenis'),
                TextColumn::make('nama')->label('Nama'),
                TextColumn::make('nomor_sertifikat')->label('Nomor')->placeholder('-'),
                TextColumn::make('penerbit')->label('Penerbit')->placeholder('-'),
                TextColumn::make('tanggal_terbit')->label('Terbit')->date('d F Y')->placeholder('-'),
                TextColumn::make('tanggal_kedaluwarsa')->label('Kedaluwarsa')->date('d F Y')->placeholder('-')->sortable(),
                TextColumn::make('status_berlaku')->label('Status')->badge(),
                TautanBerkasField::kolom('sertifikat', JenisTautan::Sertifikat),
            ])
            ->filters([
                SelectFilter::make('status_berlaku')->label('Status berlaku')->options(StatusBerlaku::class),
            ])
            ->headerActions([
                CreateAction::make()->label('Tambah sertifikasi')->using(fn (array $data): Model => $this->simpan($data)),
            ])
            ->recordActions([
                EditAction::make()->using(fn (Model $record, array $data): Model => $this->simpan($data, $record)),
                DeleteAction::make(),
            ]);
    }

    /** @param  array<string, mixed>  $data */
    private function simpan(array $data, ?Model $record = null): Model
    {
        $tautan = TautanBerkasField::pisahkan($data);
        /** @var Pegawai $pegawai */
        $pegawai = $this->getOwnerRecord();
        /** @var Sertifikasi|null $record */
        $sertifikasi = app(SimpanSertifikasi::class)->handle($pegawai, $data, $record);

        foreach ($tautan as $nama => $nilai) {
            TautanBerkasField::simpan($sertifikasi, $nama, JenisTautan::Sertifikat, $nilai, $pegawai, auth()->user());
        }

        return $sertifikasi;
    }
}

<?php

namespace App\Filament\Admin\Resources\Pegawai\RelationManagers;

use App\Actions\Riwayat\SimpanRiwayatJabatanStruktural;
use App\Enums\JenisTautan;
use App\Filament\Forms\TautanBerkasField;
use App\Models\JenisJabatanStruktural;
use App\Models\Pegawai;
use App\Models\RiwayatJabatanStruktural;
use App\Models\UnitKerja;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class StrukturalRelationManager extends RelationManager
{
    protected static string $relationship = 'riwayatJabatanStruktural';

    protected static ?string $title = 'Jabatan Struktural/Tugas Tambahan';

    protected static ?string $modelLabel = 'riwayat jabatan struktural';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('jenis_jabatan_struktural_id')->label('Jabatan')->required()->searchable()
                ->options(fn (): array => JenisJabatanStruktural::query()->where('is_aktif', true)->orderBy('urutan')->pluck('nama', 'id')->all()),
            Select::make('unit_kerja_id')->label('Unit kerja')->searchable()
                ->options(fn (): array => UnitKerja::query()->where('is_aktif', true)->orderBy('nama')->pluck('nama', 'id')->all()),
            DatePicker::make('tmt_mulai')->label('TMT mulai')->required(),
            DatePicker::make('tmt_selesai')->label('TMT selesai')->helperText('Kosongkan bila masih menjabat'),
            TextInput::make('nomor_sk')->label('Nomor SK')->required()->maxLength(100),
            DatePicker::make('tanggal_sk')->label('Tanggal SK'),
            TautanBerkasField::make('sk', JenisTautan::Sk, 'Tautan berkas SK')->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['jenisJabatanStruktural', 'unitKerja', 'tautanBerkas']))
            ->defaultSort('tmt_mulai', 'desc')
            ->columns([
                TextColumn::make('jenisJabatanStruktural.nama')->label('Jabatan'),
                TextColumn::make('unitKerja.nama')->label('Unit kerja')->placeholder('-'),
                TextColumn::make('periode')->label('Periode'),
                TextColumn::make('nomor_sk')->label('Nomor SK'),
                IconColumn::make('aktif')->label('Aktif')->boolean()->state(fn (RiwayatJabatanStruktural $record): bool => $record->isAktif()),
                TautanBerkasField::kolom('sk', JenisTautan::Sk),
            ])
            ->headerActions([
                CreateAction::make()->label('Tambah jabatan')->using(fn (array $data): Model => $this->simpan($data)),
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
        $aksi = app(SimpanRiwayatJabatanStruktural::class);
        /** @var RiwayatJabatanStruktural|null $record */
        $riwayat = $aksi->handle($pegawai, $data, $record);

        foreach ($tautan as $nama => $nilai) {
            TautanBerkasField::simpan($riwayat, $nama, JenisTautan::Sk, $nilai, $pegawai, auth()->user());
        }

        $lain = $aksi->pemegangLain($riwayat);
        if ($lain !== []) {
            Notification::make()->warning()->title('Jabatan ini juga dipegang pada periode yang sama')
                ->body('Pemegang lain: '.implode(', ', $lain))->send();
        }

        return $riwayat;
    }
}

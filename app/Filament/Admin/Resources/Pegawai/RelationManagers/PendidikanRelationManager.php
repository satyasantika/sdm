<?php

namespace App\Filament\Admin\Resources\Pegawai\RelationManagers;

use App\Actions\Riwayat\SimpanRiwayatPendidikan;
use App\Enums\JenisTautan;
use App\Filament\Forms\TautanBerkasField;
use App\Filament\Schemas\PendidikanForm;
use App\Models\Pegawai;
use App\Models\RiwayatPendidikan;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class PendidikanRelationManager extends RelationManager
{
    protected static string $relationship = 'riwayatPendidikan';

    protected static ?string $title = 'Pendidikan';

    protected static ?string $modelLabel = 'riwayat pendidikan';

    public function form(Schema $schema): Schema
    {
        /** @var Pegawai $pegawai */
        $pegawai = $this->getOwnerRecord();

        return $schema->components(PendidikanForm::components($pegawai));
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['jenjangPendidikan', 'tautanBerkas']))
            ->defaultSort('tahun_lulus', 'desc')
            ->columns([
                TextColumn::make('jenjangPendidikan.nama')->label('Jenjang'),
                TextColumn::make('nama_pt')->label('Perguruan tinggi'),
                TextColumn::make('negara')->label('Negara')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('nama_prodi')->label('Prodi')->placeholder('-'),
                TextColumn::make('bidang_ilmu')->label('Bidang ilmu')->placeholder('-'),
                TextColumn::make('gelar')->label('Gelar')->placeholder('-'),
                TextColumn::make('tahun_lulus')->label('Lulus')->placeholder('-')->sortable(),
                IconColumn::make('is_pendidikan_tertinggi')->label('Tertinggi')->boolean(),
                TautanBerkasField::kolom('ijazah', JenisTautan::Ijazah, 'Ijazah'),
                TautanBerkasField::kolom('transkrip', JenisTautan::Transkrip, 'Transkrip'),
            ])
            ->headerActions([
                CreateAction::make()->label('Tambah pendidikan')->using(fn (array $data): Model => $this->simpan($data)),
            ])
            ->recordActions([
                EditAction::make()->using(fn (Model $record, array $data): Model => $this->simpan($data, $record)),
                DeleteAction::make()->using(fn (RiwayatPendidikan $record) => app(SimpanRiwayatPendidikan::class)->hapus($record)),
            ]);
    }

    /** @param  array<string, mixed>  $data */
    private function simpan(array $data, ?Model $record = null): Model
    {
        $tautan = TautanBerkasField::pisahkan($data);
        /** @var Pegawai $pegawai */
        $pegawai = $this->getOwnerRecord();
        /** @var RiwayatPendidikan|null $record */
        $riwayat = app(SimpanRiwayatPendidikan::class)->handle($pegawai, $data, $record);

        foreach ($tautan as $nama => $nilai) {
            TautanBerkasField::simpan($riwayat, $nama, JenisTautan::from($nama), $nilai, $pegawai, auth()->user());
        }

        return $riwayat;
    }
}

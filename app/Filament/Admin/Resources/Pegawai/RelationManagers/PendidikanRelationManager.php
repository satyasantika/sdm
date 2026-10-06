<?php

namespace App\Filament\Admin\Resources\Pegawai\RelationManagers;

use App\Actions\Riwayat\SimpanRiwayatPendidikan;
use App\Enums\JenisTautan;
use App\Filament\Forms\TautanBerkasField;
use App\Models\JenjangPendidikan;
use App\Models\Pegawai;
use App\Models\RiwayatPendidikan;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
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
        return $schema->components([
            Select::make('jenjang_pendidikan_id')->label('Jenjang')->required()
                ->options(fn (): array => JenjangPendidikan::query()->orderBy('urutan')->pluck('nama', 'id')->all()),
            TextInput::make('nama_pt')->label('Perguruan tinggi')->required()->maxLength(150),
            TextInput::make('negara')->label('Negara')->default('Indonesia')->maxLength(60),
            TextInput::make('nama_prodi')->label('Program studi')->maxLength(150),
            TextInput::make('bidang_ilmu')->label('Bidang ilmu')->maxLength(150),
            TextInput::make('gelar')->label('Gelar')->maxLength(30),
            TextInput::make('tahun_masuk')->label('Tahun masuk')->numeric()->minValue(1950)->maxValue((int) now()->year),
            TextInput::make('tahun_lulus')->label('Tahun lulus')->numeric()->minValue(1950)->maxValue((int) now()->year),
            TextInput::make('nomor_ijazah')->label('Nomor ijazah')->maxLength(100),
            TextInput::make('ipk')->label('IPK')->numeric()->minValue(0)->maxValue(4)->step('0.01'),
            TextInput::make('judul_tugas_akhir')->label('Judul tugas akhir')->maxLength(500)->columnSpanFull(),
            TautanBerkasField::make('ijazah', JenisTautan::Ijazah, 'Tautan ijazah')->columnSpanFull(),
            TautanBerkasField::make('transkrip', JenisTautan::Transkrip, 'Tautan transkrip')->columnSpanFull(),
        ]);
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

<?php

namespace App\Filament\Admin\Resources\Pegawai\RelationManagers;

use App\Actions\Riwayat\SimpanPelatihan;
use App\Enums\JenisPelatihan;
use App\Enums\JenisTautan;
use App\Enums\TingkatKegiatan;
use App\Filament\Forms\TautanBerkasField;
use App\Models\Pegawai;
use App\Models\Pelatihan;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class PelatihanRelationManager extends RelationManager
{
    protected static string $relationship = 'pelatihan';

    protected static ?string $title = 'Pengembangan Diri: Pelatihan';

    protected static ?string $modelLabel = 'pelatihan';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('nama')->label('Nama')->required()->maxLength(200),
            Select::make('jenis')->label('Jenis')->options(JenisPelatihan::class)->required(),
            TextInput::make('penyelenggara')->label('Penyelenggara')->maxLength(150),
            Select::make('tingkat')->label('Tingkat')->options(TingkatKegiatan::class),
            DatePicker::make('tanggal_mulai')->label('Tanggal mulai')->required(),
            DatePicker::make('tanggal_selesai')->label('Tanggal selesai'),
            TextInput::make('jumlah_jam')->label('Jumlah jam')->numeric()->minValue(1)->maxValue(2000),
            TautanBerkasField::make('pelatihan', JenisTautan::Pelatihan, 'Tautan bukti')->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        /** @var Pegawai $pegawai */
        $pegawai = $this->getOwnerRecord();

        return $table
            ->description(fn (): string => $this->ringkasanJam($pegawai))
            ->modifyQueryUsing(fn ($query) => $query->with('tautanBerkas'))
            ->defaultSort('tanggal_mulai', 'desc')
            ->columns([
                TextColumn::make('nama')->label('Nama'),
                TextColumn::make('jenis')->label('Jenis')->badge(),
                TextColumn::make('penyelenggara')->label('Penyelenggara')->placeholder('-'),
                TextColumn::make('tanggal_mulai')->label('Mulai')->date('d F Y')->sortable(),
                TextColumn::make('jumlah_jam')->label('Jam')->placeholder('-'),
                TautanBerkasField::kolom('pelatihan', JenisTautan::Pelatihan),
            ])
            ->headerActions([
                CreateAction::make()->label('Tambah pelatihan')->using(fn (array $data): Model => $this->simpan($data)),
            ])
            ->recordActions([
                EditAction::make()->using(fn (Model $record, array $data): Model => $this->simpan($data, $record)),
                DeleteAction::make(),
            ]);
    }

    private function ringkasanJam(Pegawai $pegawai): string
    {
        $per = $pegawai->jamPelatihanPerTahun();

        return $per === []
            ? 'Belum ada jam pelatihan tercatat.'
            : 'Total jam pelatihan: '.collect($per)->map(fn (int $jam, int $tahun): string => "{$tahun}: {$jam} jam")->implode(' · ');
    }

    /** @param  array<string, mixed>  $data */
    private function simpan(array $data, ?Model $record = null): Model
    {
        $tautan = TautanBerkasField::pisahkan($data);
        /** @var Pegawai $pegawai */
        $pegawai = $this->getOwnerRecord();
        /** @var Pelatihan|null $record */
        $pelatihan = app(SimpanPelatihan::class)->handle($pegawai, $data, $record);

        foreach ($tautan as $nama => $nilai) {
            TautanBerkasField::simpan($pelatihan, $nama, JenisTautan::Pelatihan, $nilai, $pegawai, auth()->user());
        }

        return $pelatihan;
    }
}

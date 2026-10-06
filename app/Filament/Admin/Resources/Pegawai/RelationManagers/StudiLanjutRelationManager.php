<?php

namespace App\Filament\Admin\Resources\Pegawai\RelationManagers;

use App\Actions\Riwayat\SimpanStudiLanjut;
use App\Enums\JenisStudiLanjut;
use App\Enums\JenisTautan;
use App\Enums\StatusAktifPegawai;
use App\Enums\StatusStudiLanjut;
use App\Filament\Forms\TautanBerkasField;
use App\Models\JenjangPendidikan;
use App\Models\Pegawai;
use App\Models\StudiLanjut;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class StudiLanjutRelationManager extends RelationManager
{
    protected static string $relationship = 'studiLanjut';

    protected static ?string $title = 'Studi Lanjut';

    protected static ?string $modelLabel = 'studi lanjut';

    public function form(Schema $schema): Schema
    {
        /** @var Pegawai $pegawai */
        $pegawai = $this->getOwnerRecord();

        return $schema->components([
            Select::make('jenis')->label('Jenis')->options(JenisStudiLanjut::class)->required()->live(),
            Select::make('jenjang_pendidikan_id')->label('Jenjang')->required()
                ->options(fn (): array => JenjangPendidikan::query()->orderBy('urutan')->pluck('nama', 'id')->all()),
            TextInput::make('nama_pt')->label('Perguruan tinggi')->required()->maxLength(150),
            TextInput::make('negara')->label('Negara')->default('Indonesia')->maxLength(60),
            TextInput::make('nama_prodi')->label('Program studi')->maxLength(150),
            TextInput::make('sumber_biaya')->label('Sumber biaya')->maxLength(100)->placeholder('BPI, LPDP, mandiri'),
            TextInput::make('nomor_sk')->label('Nomor SK')->maxLength(100),
            DatePicker::make('tanggal_mulai')->label('Tanggal mulai')->required(),
            DatePicker::make('tanggal_selesai_rencana')->label('Rencana selesai'),
            DatePicker::make('tanggal_selesai_aktual')->label('Selesai aktual'),
            Select::make('status')->label('Status')->options(StatusStudiLanjut::class)->required()->default(StatusStudiLanjut::Berjalan->value)->live(),
            Checkbox::make('sinkron_status')->label($this->labelSinkron($pegawai))->default(true)->dehydrated(true)
                ->visible(fn ($get): bool => $this->bolehSinkron($pegawai, $get('jenis'), $get('status'))),
            TautanBerkasField::make('sk', JenisTautan::Sk, 'Tautan SK tugas/izin belajar')->columnSpanFull(),
        ]);
    }

    private function labelSinkron(Pegawai $pegawai): string
    {
        return $pegawai->status_aktif === StatusAktifPegawai::TugasBelajar
            ? 'Kembalikan status pegawai menjadi aktif bila studi selesai/berhenti'
            : 'Ubah status pegawai menjadi tugas belajar';
    }

    private function bolehSinkron(Pegawai $pegawai, mixed $jenis, mixed $status): bool
    {
        $jenis = $jenis instanceof JenisStudiLanjut ? $jenis->value : $jenis;

        return $jenis === JenisStudiLanjut::TugasBelajar->value && (bool) auth()->user()?->can('pegawai.ubah');
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['jenjangPendidikan', 'tautanBerkas']))
            ->defaultSort('tanggal_mulai', 'desc')
            ->columns([
                TextColumn::make('jenis')->label('Jenis')->badge(),
                TextColumn::make('jenjangPendidikan.nama')->label('Jenjang'),
                TextColumn::make('nama_pt')->label('Perguruan tinggi'),
                TextColumn::make('nama_prodi')->label('Prodi')->placeholder('-'),
                TextColumn::make('tanggal_mulai')->label('Mulai')->date('d F Y')->sortable(),
                TextColumn::make('tanggal_selesai_rencana')->label('Rencana selesai')->date('d F Y')->placeholder('-'),
                TextColumn::make('status')->label('Status')->badge(),
                TautanBerkasField::kolom('sk', JenisTautan::Sk),
            ])
            ->headerActions([
                CreateAction::make()->label('Tambah studi lanjut')->using(fn (array $data): Model => $this->simpan($data)),
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
        $sinkron = (bool) ($data['sinkron_status'] ?? true);
        unset($data['sinkron_status']);
        /** @var Pegawai $pegawai */
        $pegawai = $this->getOwnerRecord();
        /** @var StudiLanjut|null $record */
        $studi = app(SimpanStudiLanjut::class)->handle($pegawai, $data, auth()->user(), $record, $sinkron);

        foreach ($tautan as $nama => $nilai) {
            TautanBerkasField::simpan($studi, $nama, JenisTautan::Sk, $nilai, $pegawai, auth()->user());
        }

        $studi->loadMissing('jenjangPendidikan');
        if ($studi->status === StatusStudiLanjut::Selesai && in_array($studi->jenjangPendidikan->kode, ['S2', 'S3'], true)) {
            Notification::make()->info()->title('Tambahkan riwayat pendidikan')
                ->body('Studi lanjut selesai: catat gelar yang diperoleh di tab Pendidikan.')->send();
        }

        return $studi;
    }
}

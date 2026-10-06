<?php

namespace App\Filament\Admin\Resources\Pegawai\RelationManagers;

use App\Actions\Riwayat\SimpanStudiLanjut;
use App\Enums\JenisTautan;
use App\Enums\StatusStudiLanjut;
use App\Filament\Forms\TautanBerkasField;
use App\Filament\Schemas\StudiLanjutForm;
use App\Models\Pegawai;
use App\Models\StudiLanjut;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
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

        return $schema->components(StudiLanjutForm::components($pegawai));
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

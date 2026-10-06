<?php

namespace App\Filament\Admin\Resources\Pegawai\RelationManagers;

use App\Actions\Riwayat\SimpanRiwayatJabatanStruktural;
use App\Enums\JenisTautan;
use App\Filament\Forms\TautanBerkasField;
use App\Filament\Schemas\StrukturalForm;
use App\Models\Pegawai;
use App\Models\RiwayatJabatanStruktural;
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

class StrukturalRelationManager extends RelationManager
{
    protected static string $relationship = 'riwayatJabatanStruktural';

    protected static ?string $title = 'Jabatan Struktural/Tugas Tambahan';

    protected static ?string $modelLabel = 'riwayat jabatan struktural';

    public function form(Schema $schema): Schema
    {
        /** @var Pegawai $pegawai */
        $pegawai = $this->getOwnerRecord();

        return $schema->components(StrukturalForm::components($pegawai));
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

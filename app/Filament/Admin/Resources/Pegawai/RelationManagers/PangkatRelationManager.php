<?php

namespace App\Filament\Admin\Resources\Pegawai\RelationManagers;

use App\Actions\Riwayat\HapusRiwayatPangkat;
use App\Actions\Riwayat\SimpanRiwayatPangkat;
use App\Enums\JenisTautan;
use App\Filament\Forms\TautanBerkasField;
use App\Filament\Schemas\PangkatForm;
use App\Models\Pegawai;
use App\Models\RiwayatPangkat;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class PangkatRelationManager extends RelationManager
{
    protected static string $relationship = 'riwayatPangkat';

    protected static ?string $title = 'Pangkat';

    protected static ?string $modelLabel = 'riwayat pangkat';

    public function form(Schema $schema): Schema
    {
        /** @var Pegawai $pegawai */
        $pegawai = $this->getOwnerRecord();

        return $schema->components(PangkatForm::components($pegawai));
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['golongan', 'tautanBerkas']))
            ->defaultSort('tmt', 'desc')
            ->columns([
                TextColumn::make('golongan.label')->label('Golongan'),
                TextColumn::make('tmt')->label('TMT')->date('d F Y')->sortable(),
                TextColumn::make('jenis_kenaikan')->label('Jenis kenaikan')->badge(),
                TextColumn::make('masa_kerja')->label('Masa kerja')->state(fn (RiwayatPangkat $record): string => $record->masaKerjaLabel()),
                TextColumn::make('nomor_sk')->label('Nomor SK'),
                IconColumn::make('is_terkini')->label('Terkini')->boolean(),
                TautanBerkasField::kolom('sk', JenisTautan::Sk),
            ])
            ->headerActions([
                CreateAction::make()->label('Tambah pangkat')->using(fn (array $data): Model => $this->simpan($data)),
            ])
            ->recordActions([
                EditAction::make()->using(fn (Model $record, array $data): Model => $this->simpan($data, $record)),
                DeleteAction::make()->using(fn (RiwayatPangkat $record) => app(HapusRiwayatPangkat::class)->handle($record)),
            ]);
    }

    /** @param  array<string, mixed>  $data */
    private function simpan(array $data, ?Model $record = null): Model
    {
        $tautan = TautanBerkasField::pisahkan($data);
        /** @var Pegawai $pegawai */
        $pegawai = $this->getOwnerRecord();
        /** @var RiwayatPangkat|null $record */
        $riwayat = app(SimpanRiwayatPangkat::class)->handle($pegawai, $data, $record);

        foreach ($tautan as $nama => $nilai) {
            TautanBerkasField::simpan($riwayat, $nama, JenisTautan::Sk, $nilai, $pegawai, auth()->user());
        }

        return $riwayat;
    }
}

<?php

namespace App\Filament\Admin\Resources\Pegawai\RelationManagers;

use App\Actions\Riwayat\HapusRiwayatJabatanFungsional;
use App\Actions\Riwayat\SimpanRiwayatJabatanFungsional;
use App\Enums\JenisTautan;
use App\Filament\Forms\TautanBerkasField;
use App\Filament\Schemas\JabatanFungsionalForm;
use App\Models\Pegawai;
use App\Models\RiwayatJabatanFungsional;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class JabatanFungsionalRelationManager extends RelationManager
{
    protected static string $relationship = 'riwayatJabatanFungsional';

    protected static ?string $title = 'Jabatan Fungsional';

    protected static ?string $modelLabel = 'riwayat jabatan fungsional';

    public function form(Schema $schema): Schema
    {
        /** @var Pegawai $pegawai */
        $pegawai = $this->getOwnerRecord();

        return $schema->components(JabatanFungsionalForm::components($pegawai));
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['jabatanFungsional', 'tautanBerkas']))
            ->defaultSort('tmt', 'desc')
            ->columns([
                TextColumn::make('jabatanFungsional.nama')->label('Jabatan'),
                TextColumn::make('tmt')->label('TMT')->date('d F Y')->sortable(),
                TextColumn::make('nomor_sk')->label('Nomor SK'),
                TextColumn::make('tanggal_sk')->label('Tanggal SK')->date('d F Y')->placeholder('-'),
                TextColumn::make('angka_kredit')->label('Angka kredit')->placeholder('-'),
                IconColumn::make('is_terkini')->label('Terkini')->boolean(),
                TautanBerkasField::kolom('sk', JenisTautan::Sk),
            ])
            ->headerActions([
                CreateAction::make()->label('Tambah riwayat')
                    ->using(fn (array $data): Model => $this->simpan($data)),
            ])
            ->recordActions([
                EditAction::make()->using(fn (Model $record, array $data): Model => $this->simpan($data, $record)),
                DeleteAction::make()->using(function (RiwayatJabatanFungsional $record): void {
                    app(HapusRiwayatJabatanFungsional::class)->handle($record);
                }),
            ]);
    }

    /** @param  array<string, mixed>  $data */
    private function simpan(array $data, ?Model $record = null): Model
    {
        $tautan = TautanBerkasField::pisahkan($data);
        /** @var Pegawai $pegawai */
        $pegawai = $this->getOwnerRecord();
        /** @var RiwayatJabatanFungsional|null $record */
        $riwayat = app(SimpanRiwayatJabatanFungsional::class)->handle($pegawai, $data, auth()->user(), $record);

        foreach ($tautan as $nama => $nilai) {
            TautanBerkasField::simpan($riwayat, $nama, JenisTautan::Sk, $nilai, $pegawai, auth()->user());
        }

        return $riwayat;
    }
}

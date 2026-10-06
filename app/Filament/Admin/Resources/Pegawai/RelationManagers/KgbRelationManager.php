<?php

namespace App\Filament\Admin\Resources\Pegawai\RelationManagers;

use App\Actions\Riwayat\SimpanRiwayatKgb;
use App\Enums\JenisTautan;
use App\Filament\Forms\TautanBerkasField;
use App\Filament\Schemas\KgbForm;
use App\Models\Pegawai;
use App\Models\RiwayatKgb;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class KgbRelationManager extends RelationManager
{
    protected static string $relationship = 'riwayatKgb';

    protected static ?string $title = 'Kenaikan Gaji Berkala';

    protected static ?string $modelLabel = 'riwayat KGB';

    public function form(Schema $schema): Schema
    {
        /** @var Pegawai $pegawai */
        $pegawai = $this->getOwnerRecord();

        return $schema->components(KgbForm::components($pegawai));
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['golongan', 'tautanBerkas']))
            ->defaultSort('tmt', 'desc')
            ->columns([
                TextColumn::make('tmt')->label('TMT')->date('d F Y')->sortable(),
                TextColumn::make('golongan.label')->label('Golongan')->placeholder('-'),
                TextColumn::make('gaji_pokok')->label('Gaji pokok')->placeholder('-')
                    ->formatStateUsing(fn (?string $state): string => $state === null ? '-' : 'Rp '.number_format((float) $state, 2, ',', '.')),
                TextColumn::make('masa_kerja')->label('Masa kerja')->state(fn (RiwayatKgb $record): string => $record->masaKerjaLabel()),
                TextColumn::make('nomor_sk')->label('Nomor SK'),
                IconColumn::make('is_terkini')->label('Terkini')->boolean(),
                TautanBerkasField::kolom('sk', JenisTautan::Sk),
            ])
            ->headerActions([
                CreateAction::make()->label('Tambah KGB')->using(fn (array $data): Model => $this->simpan($data)),
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
        /** @var RiwayatKgb|null $record */
        $riwayat = app(SimpanRiwayatKgb::class)->handle($pegawai, $data, $record);

        foreach ($tautan as $nama => $nilai) {
            TautanBerkasField::simpan($riwayat, $nama, JenisTautan::Sk, $nilai, $pegawai, auth()->user());
        }

        return $riwayat;
    }
}

<?php

namespace App\Filament\Admin\Resources\Pegawai\RelationManagers;

use App\Actions\Riwayat\SimpanPenghargaan;
use App\Enums\JenisTautan;
use App\Filament\Forms\TautanBerkasField;
use App\Filament\Schemas\PenghargaanForm;
use App\Models\Pegawai;
use App\Models\Penghargaan;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class PenghargaanRelationManager extends RelationManager
{
    protected static string $relationship = 'penghargaan';

    protected static ?string $title = 'Pengembangan Diri: Penghargaan';

    protected static ?string $modelLabel = 'penghargaan';

    public function form(Schema $schema): Schema
    {
        /** @var Pegawai $pegawai */
        $pegawai = $this->getOwnerRecord();

        return $schema->components(PenghargaanForm::components($pegawai));
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('tautanBerkas'))
            ->defaultSort('tanggal', 'desc')
            ->columns([
                TextColumn::make('kategori')->label('Kategori')->badge(),
                TextColumn::make('nama')->label('Nama'),
                TextColumn::make('pemberi')->label('Pemberi')->placeholder('-'),
                TextColumn::make('tingkat')->label('Tingkat')->badge(),
                TextColumn::make('tanggal')->label('Tanggal')->date('d F Y')->placeholder('-')->sortable(),
                TautanBerkasField::kolom('penghargaan', JenisTautan::Penghargaan),
            ])
            ->headerActions([
                CreateAction::make()->label('Tambah penghargaan')->using(fn (array $data): Model => $this->simpan($data)),
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
        /** @var Penghargaan|null $record */
        $penghargaan = app(SimpanPenghargaan::class)->handle($pegawai, $data, $record);

        foreach ($tautan as $nama => $nilai) {
            TautanBerkasField::simpan($penghargaan, $nama, JenisTautan::Penghargaan, $nilai, $pegawai, auth()->user());
        }

        return $penghargaan;
    }
}

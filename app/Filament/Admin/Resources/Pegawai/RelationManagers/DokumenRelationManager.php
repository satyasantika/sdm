<?php

namespace App\Filament\Admin\Resources\Pegawai\RelationManagers;

use App\Actions\Riwayat\SimpanDokumenPegawai;
use App\Enums\JenisTautan;
use App\Enums\Peran;
use App\Enums\StatusBerlaku;
use App\Filament\Forms\TautanBerkasField;
use App\Filament\Schemas\DokumenForm;
use App\Models\DokumenPegawai;
use App\Models\Pegawai;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HtmlString;
use Illuminate\Validation\ValidationException;

class DokumenRelationManager extends RelationManager
{
    protected static string $relationship = 'dokumen';

    protected static ?string $title = 'Dokumen';

    protected static ?string $modelLabel = 'dokumen';

    public function form(Schema $schema): Schema
    {
        /** @var Pegawai $pegawai */
        $pegawai = $this->getOwnerRecord();

        return $schema->components(DokumenForm::components($pegawai));
    }

    public function table(Table $table): Table
    {
        $user = auth()->user();
        $admin = (bool) $user?->hasAnyRole([Peran::SuperAdmin->value, Peran::AdminKepegawaian->value]);

        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['jenisDokumen', 'tautanBerkas'])
                ->when(! $admin, fn (Builder $q) => $q->whereHas('jenisDokumen', fn (Builder $j) => $j->where('is_identitas', false))))
            ->defaultSort('tanggal_kedaluwarsa')
            ->columns([
                TextColumn::make('jenisDokumen.nama')->label('Jenis'),
                TextColumn::make('nomor')->label('Nomor')->placeholder('-'),
                TextColumn::make('tanggal_terbit')->label('Terbit')->date('d F Y')->placeholder('-'),
                TextColumn::make('tanggal_kedaluwarsa')->label('Kedaluwarsa')->date('d F Y')->placeholder('-')->sortable(),
                TextColumn::make('status_berlaku')->label('Status')->badge(),
                TextColumn::make('berkas')->label('Berkas')
                    ->state(fn (DokumenPegawai $record) => $record->berkas())
                    ->formatStateUsing(fn ($state) => new HtmlString(view('components.tautan-berkas', ['tautan' => $state])->render()))
                    ->html(),
            ])
            ->filters([SelectFilter::make('status_berlaku')->label('Status')->options(StatusBerlaku::class)])
            ->headerActions([
                CreateAction::make()->label('Tambah dokumen')->using(fn (array $data): Model => $this->simpan($data)),
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

        // Dokumen baru wajib memiliki satu tautan berkas (sensitif).
        if ($record === null && trim($tautan['dokumen']['url'] ?? '') === '') {
            throw ValidationException::withMessages(['tautan_dokumen' => 'Dokumen wajib disertai tautan berkas.']);
        }

        /** @var DokumenPegawai|null $record */
        $dokumen = app(SimpanDokumenPegawai::class)->handle($pegawai, $data, auth()->user(), $record);

        foreach ($tautan as $nama => $nilai) {
            TautanBerkasField::simpan($dokumen, $nama, $dokumen->jenisTautanUntuk($nama) ?? JenisTautan::DokumenKepegawaian, $nilai, $pegawai, auth()->user());
        }

        return $dokumen;
    }
}

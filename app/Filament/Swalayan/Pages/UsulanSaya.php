<?php

namespace App\Filament\Swalayan\Pages;

use App\Actions\Usulan\AjukanUsulanPerubahan;
use App\Actions\Usulan\BatalkanUsulanPerubahan;
use App\Actions\Usulan\PerbaruiUsulanPerubahan;
use App\Enums\JenisTautan;
use App\Enums\StatusUsulan;
use App\Exceptions\TransisiUsulanTidakSah;
use App\Filament\Forms\TautanBerkasField;
use App\Filament\Swalayan\Support\AksiUsulan;
use App\Models\UsulanPerubahan;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

class UsulanSaya extends Page implements HasActions, HasTable
{
    use InteractsWithActions;
    use InteractsWithTable;

    protected string $view = 'filament.swalayan.pages.usulan-saya';

    protected static ?string $title = 'Usulan Saya';

    protected static ?string $slug = 'usulan-saya';

    protected static ?int $navigationSort = 3;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPaperAirplane;

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => UsulanPerubahan::query()->where('diajukan_oleh', auth()->id()))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('jenis')->label('Jenis')->badge(),
                TextColumn::make('target')->label('Data yang diubah')->state(fn (UsulanPerubahan $record): string => $record->labelTarget()),
                TextColumn::make('diajukan_at')->label('Diajukan')->dateTime('d F Y H:i')->placeholder('-')->sortable(),
                TextColumn::make('status')->label('Status')->badge(),
                TextColumn::make('catatan_verifikator')->label('Catatan verifikator')->placeholder('-')->wrap(),
            ])
            ->recordActions([
                Action::make('lihat')->label('Lihat')->icon(Heroicon::OutlinedEye)
                    ->modalHeading('Rincian usulan')->modalSubmitAction(false)->modalCancelActionLabel('Tutup')
                    ->modalContent(fn (UsulanPerubahan $record) => view('filament.swalayan.diff-usulan', ['usulan' => $record])),
                $this->aksiUbahKirimUlang(),
                Action::make('kirim')->label('Kirim')->icon(Heroicon::OutlinedPaperAirplane)->requiresConfirmation()
                    ->visible(fn (UsulanPerubahan $record): bool => $record->status === StatusUsulan::Draf)
                    ->action(fn (UsulanPerubahan $record) => $this->kirim($record)),
                Action::make('batalkan')->label('Batalkan')->icon(Heroicon::OutlinedXMark)->color('danger')->requiresConfirmation()
                    ->visible(fn (UsulanPerubahan $record): bool => $record->status->isAktif())
                    ->action(function (UsulanPerubahan $record): void {
                        try {
                            app(BatalkanUsulanPerubahan::class)->handle($record, auth()->user());
                            Notification::make()->success()->title('Usulan dibatalkan.')->send();
                        } catch (TransisiUsulanTidakSah $e) {
                            Notification::make()->danger()->title($e->getMessage())->send();
                        }
                    }),
            ]);
    }

    private function aksiUbahKirimUlang(): Action
    {
        return Action::make('ubah_kirim_ulang')->label('Ubah & kirim ulang')->icon(Heroicon::OutlinedPencilSquare)->modalWidth('3xl')
            ->visible(fn (UsulanPerubahan $record): bool => $record->status === StatusUsulan::Dikembalikan && $record->jenis->value !== 'hapus_riwayat')
            ->fillForm(fn (UsulanPerubahan $record): array => [...($record->data_baru ?? []), 'alasan' => $record->alasan])
            ->schema(fn (UsulanPerubahan $record): array => [
                ...AksiUsulan::FORM[$record->target_tabel]::components($record->pegawai),
                Textarea::make('alasan')->label('Alasan perubahan')->maxLength(500)->columnSpanFull(),
                TautanBerkasField::make('bukti', JenisTautan::BuktiUsulan, 'Tautan bukti pendukung')->columnSpanFull(),
            ])
            ->action(function (array $data, UsulanPerubahan $record): void {
                try {
                    $alasan = $data['alasan'] ?? null;
                    unset($data['alasan']);
                    $tautan = TautanBerkasField::pisahkan($data);
                    unset($tautan['bukti']);
                    foreach ($tautan as $nama => $nilai) {
                        if ($nilai['url'] !== '') {
                            $data['tautan'][$nama] = $nilai['url'];
                        }
                    }

                    $record = app(PerbaruiUsulanPerubahan::class)->handle($record, auth()->user(), $data, $alasan);
                    $this->kirim($record);
                } catch (ValidationException $e) {
                    Notification::make()->danger()->title('Usulan tidak dapat dikirim')->body(collect($e->errors())->flatten()->implode("\n"))->send();
                }
            });
    }

    public function kirim(UsulanPerubahan $usulan): void
    {
        try {
            app(AjukanUsulanPerubahan::class)->handle($usulan, auth()->user());
            Notification::make()->success()->title('Usulan terkirim dan menunggu verifikasi admin kepegawaian.')->send();
        } catch (TransisiUsulanTidakSah $e) {
            Notification::make()->danger()->title($e->getMessage())->send();
        }
    }
}

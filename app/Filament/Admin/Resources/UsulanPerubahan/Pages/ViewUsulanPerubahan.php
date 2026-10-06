<?php

namespace App\Filament\Admin\Resources\UsulanPerubahan\Pages;

use App\Actions\Usulan\KembalikanUsulanPerubahan;
use App\Actions\Usulan\SetujuiUsulanPerubahan;
use App\Actions\Usulan\TampilkanDataUsulanSensitif;
use App\Actions\Usulan\TolakUsulanPerubahan;
use App\Enums\StatusUsulan;
use App\Exceptions\KonflikDataUsulan;
use App\Exceptions\TransisiUsulanTidakSah;
use App\Exceptions\UsulanSedangDiproses;
use App\Filament\Admin\Resources\UsulanPerubahan\UsulanPerubahanResource;
use App\Models\UsulanPerubahan;
use App\Support\RegistriTargetUsulan;
use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;
use Illuminate\Validation\ValidationException;

class ViewUsulanPerubahan extends ViewRecord
{
    protected static string $resource = UsulanPerubahanResource::class;

    protected function getHeaderActions(): array
    {
        $bolehMemutuskan = fn (UsulanPerubahan $record): bool => (bool) auth()->user()?->can('verifikasi', $record)
            && $record->status === StatusUsulan::Diajukan;

        return [
            Action::make('setujui')->label('Setujui')->icon(Heroicon::OutlinedCheck)->color('success')
                ->requiresConfirmation()->modalHeading('Setujui usulan ini?')
                ->modalDescription('Data usulan akan diterapkan ke data pegawai.')
                ->schema(fn (UsulanPerubahan $record): array => $record->adaKonflik() ? [
                    Checkbox::make('konfirmasi_konflik')->label('Tetap setujui meskipun data berubah sejak usulan diajukan'),
                ] : [])
                ->visible($bolehMemutuskan)
                ->action(fn (array $data, UsulanPerubahan $record) => $this->putuskan(
                    fn () => app(SetujuiUsulanPerubahan::class)->handle($record, auth()->user(), (bool) ($data['konfirmasi_konflik'] ?? false)),
                    'Usulan disetujui dan diterapkan.',
                )),
            Action::make('tolak')->label('Tolak')->icon(Heroicon::OutlinedXMark)->color('danger')
                ->modalHeading('Tolak usulan')
                ->schema([Textarea::make('catatan')->label('Catatan penolakan')->required()->minLength(10)->maxLength(500)])
                ->visible($bolehMemutuskan)
                ->action(fn (array $data, UsulanPerubahan $record) => $this->putuskan(
                    fn () => app(TolakUsulanPerubahan::class)->handle($record, auth()->user(), $data['catatan']),
                    'Usulan ditolak.',
                )),
            Action::make('kembalikan')->label('Kembalikan')->icon(Heroicon::OutlinedArrowUturnLeft)->color('warning')
                ->modalHeading('Kembalikan usulan untuk diperbaiki')
                ->schema([Textarea::make('catatan')->label('Catatan perbaikan')->required()->minLength(10)->maxLength(500)])
                ->visible($bolehMemutuskan)
                ->action(fn (array $data, UsulanPerubahan $record) => $this->putuskan(
                    fn () => app(KembalikanUsulanPerubahan::class)->handle($record, auth()->user(), $data['catatan']),
                    'Usulan dikembalikan ke pengusul.',
                )),
            Action::make('tampil_sensitif')->label('Tampilkan data sensitif')->icon(Heroicon::OutlinedEye)->color('gray')
                ->visible(fn (UsulanPerubahan $record): bool => (bool) auth()->user()?->can('pegawai.lihat-sensitif')
                    && RegistriTargetUsulan::kolomSensitif($record->target_tabel) !== []
                    && array_intersect(RegistriTargetUsulan::kolomSensitif($record->target_tabel), array_keys([...($record->data_baru ?? []), ...($record->data_lama ?? [])])) !== [])
                ->modalHeading('Data sensitif usulan')->modalDescription('Akses ini dicatat di log audit.')
                ->fillForm(fn (UsulanPerubahan $record): array => ['isi' => collect(app(TampilkanDataUsulanSensitif::class)->handle(auth()->user(), $record))
                    ->map(fn (array $v, string $kolom): string => "{$kolom}: ".($v['lama'] ?? '—').' → '.($v['baru'] ?? '—'))->implode("\n")])
                ->schema([Textarea::make('isi')->label('Lama → baru')->readOnly()->rows(4)])
                ->modalSubmitAction(false)->modalCancelActionLabel('Tutup'),
        ];
    }

    private function putuskan(Closure $aksi, string $pesanSukses): void
    {
        try {
            $aksi();
        } catch (KonflikDataUsulan $e) {
            Notification::make()->warning()->title('Data berubah sejak usulan diajukan')->body($e->getMessage())->send();

            return;
        } catch (UsulanSedangDiproses|TransisiUsulanTidakSah $e) {
            Notification::make()->danger()->title($e->getMessage())->send();

            return;
        } catch (ValidationException $e) {
            Notification::make()->danger()->title('Usulan tidak dapat diproses')->body(collect($e->errors())->flatten()->implode("\n"))->send();

            return;
        }

        Notification::make()->success()->title($pesanSukses)->send();
        $this->record->refresh();
    }
}

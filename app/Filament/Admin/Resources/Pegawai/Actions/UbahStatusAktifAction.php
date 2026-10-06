<?php

namespace App\Filament\Admin\Resources\Pegawai\Actions;

use App\Actions\Pegawai\UbahStatusAktifPegawai;
use App\Enums\StatusAktifPegawai;
use App\Exceptions\StatusTidakDiizinkan;
use App\Models\Pegawai;
use Carbon\CarbonImmutable;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;

class UbahStatusAktifAction
{
    public static function make(): Action
    {
        return Action::make('ubahStatus')
            ->label('Ubah status keaktifan')
            ->icon(Heroicon::OutlinedArrowsRightLeft)
            ->visible(fn (Pegawai $record): bool => (bool) auth()->user()?->can('ubahStatus', $record)
                && $record->status_aktif->tujuanTersedia() !== [])
            ->schema(fn (Pegawai $record): array => [
                Select::make('ke')->label('Status tujuan')->required()
                    ->options(collect($record->status_aktif->tujuanTersedia())->mapWithKeys(fn (StatusAktifPegawai $s) => [$s->value => $s->getLabel()])->all()),
                DatePicker::make('tmt')->label('TMT')->required()->default(now()),
                TextInput::make('nomor_sk')->label('Nomor SK')->maxLength(100),
                TextInput::make('catatan')->label('Catatan')->maxLength(255),
            ])
            ->action(function (array $data, Pegawai $record): void {
                try {
                    app(UbahStatusAktifPegawai::class)->handle(
                        $record,
                        StatusAktifPegawai::from($data['ke']),
                        CarbonImmutable::parse($data['tmt']),
                        $data['nomor_sk'] ?? null,
                        $data['catatan'] ?? null,
                        auth()->user(),
                    );
                } catch (StatusTidakDiizinkan $e) {
                    Notification::make()->title($e->getMessage())->danger()->send();

                    return;
                }

                Notification::make()->title('Status keaktifan diubah')->success()->send();
            });
    }
}

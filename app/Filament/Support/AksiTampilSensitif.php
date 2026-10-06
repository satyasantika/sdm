<?php

namespace App\Filament\Support;

use App\Actions\Pegawai\TampilkanDataSensitif;
use App\Models\Pegawai;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Gate;

/** Aksi "mata": menampilkan nilai polos di modal (bukan di HTML halaman) dengan otorisasi, rate limit, dan log. */
class AksiTampilSensitif
{
    public static function make(string $kolom, string $label): Action
    {
        return Action::make('tampil_'.$kolom)
            ->icon(Heroicon::OutlinedEye)
            ->tooltip('Tampilkan '.$label)
            ->visible(fn (Pegawai $record): bool => Gate::allows('viewSensitive', $record))
            ->modalHeading('Tampilkan '.$label)
            ->modalDescription('Akses ini dicatat di log audit.')
            ->fillForm(fn (Pegawai $record): array => [
                'nilai' => app(TampilkanDataSensitif::class)->handle(auth()->user(), $record, $kolom),
            ])
            ->schema([TextInput::make('nilai')->label($label)->readOnly()])
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Tutup');
    }
}

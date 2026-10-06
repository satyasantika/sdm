<?php

namespace App\Filament\Admin\Resources\TokenApi\Pages;

use App\Actions\Api\BuatTokenKlienApi;
use App\Filament\Admin\Resources\TokenApi\TokenApiResource;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;

class ListTokenApi extends ListRecords
{
    protected static string $resource = TokenApiResource::class;

    /** Token polos hanya disimpan sampai modal "tampilkan token" ditutup. */
    #[Locked]
    public ?string $tokenPolos = null;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('buatToken')
                ->label('Buat token klien')
                ->icon('heroicon-o-plus')
                ->authorize(fn (): bool => (bool) auth()->user()?->can('api.kelola-token'))
                ->schema([
                    TextInput::make('klien')->label('Nama klien')->helperText('Contoh: akreditasi, keuangan, lms')->required()->maxLength(60)->regex('/^[\pL\pN \-]+$/u'),
                    TextInput::make('token')->label('Nama token')->required()->maxLength(80),
                    TextInput::make('hari')->label('Masa berlaku (hari)')->numeric()->minValue(1)->maxValue(730)->default(BuatTokenKlienApi::HARI_BERLAKU_DEFAULT)->required(),
                ])
                ->action(function (array $data): void {
                    [, $this->tokenPolos] = app(BuatTokenKlienApi::class)->handle(auth()->user(), $data['klien'], $data['token'], (int) $data['hari']);
                    $this->js('setTimeout(() => $wire.mountAction(\'tampilToken\'), 50)');
                }),
            Action::make('tampilToken')
                ->label('Token baru')
                ->modalHeading('Token API baru')
                ->modalContent(fn (): View => view('filament.token-api.token-polos', ['token' => $this->tokenPolos]))
                ->modalSubmitActionLabel('Saya sudah menyalin')
                ->modalCancelAction(false)
                ->closeModalByClickingAway(false)
                ->action(fn () => $this->tokenPolos = null)
                ->visible(fn (): bool => filled($this->tokenPolos)),
        ];
    }
}

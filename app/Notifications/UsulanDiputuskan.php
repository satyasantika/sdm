<?php

namespace App\Notifications;

use App\Filament\Swalayan\Pages\UsulanSaya;
use App\Models\UsulanPerubahan;
use Filament\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UsulanDiputuskan extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly UsulanPerubahan $usulan)
    {
        $this->onQueue('notifikasi');
    }

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    private function judul(): string
    {
        return 'Usulan Anda '.mb_strtolower($this->usulan->status->getLabel());
    }

    private function ringkas(): string
    {
        $isi = $this->usulan->jenis->getLabel().': '.$this->usulan->labelTarget();

        return $this->usulan->catatan_verifikator ? $isi.'. Catatan: '.$this->usulan->catatan_verifikator : $isi;
    }

    /** @return array<string, mixed> */
    public function toDatabase(object $notifiable): array
    {
        return FilamentNotification::make()
            ->title($this->judul())
            ->body($this->ringkas())
            ->icon('heroicon-o-check-circle')
            ->actions([Action::make('lihat')->label('Lihat usulan')->url(UsulanSaya::getUrl(panel: 'swalayan'))])
            ->getDatabaseMessage();
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->judul())
            ->line($this->ringkas())
            ->action('Lihat usulan saya', UsulanSaya::getUrl(panel: 'swalayan'));
    }
}

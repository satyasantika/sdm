<?php

namespace App\Notifications;

use App\Filament\Admin\Resources\UsulanPerubahan\UsulanPerubahanResource;
use App\Models\UsulanPerubahan;
use Filament\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UsulanDiajukan extends Notification implements ShouldQueue
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

    private function url(): string
    {
        return UsulanPerubahanResource::getUrl('view', ['record' => $this->usulan]);
    }

    private function ringkas(): string
    {
        return $this->usulan->jenis->getLabel().': '.$this->usulan->labelTarget();
    }

    /** @return array<string, mixed> */
    public function toDatabase(object $notifiable): array
    {
        return FilamentNotification::make()
            ->title('Usulan perubahan baru')
            ->body($this->ringkas())
            ->icon('heroicon-o-inbox-arrow-down')
            ->actions([Action::make('lihat')->label('Tinjau')->url($this->url())])
            ->getDatabaseMessage();
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Usulan perubahan data menunggu verifikasi')
            ->line('Ada usulan perubahan data pegawai yang menunggu verifikasi Anda.')
            ->line($this->ringkas())
            ->action('Tinjau usulan', $this->url());
    }
}

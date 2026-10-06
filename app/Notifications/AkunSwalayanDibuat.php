<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AkunSwalayanDibuat extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly string $urlAturKataSandi)
    {
        $this->onQueue('notifikasi');
    }

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Akun SDM FKIP Unsil Anda telah dibuat')
            ->greeting('Halo!')
            ->line('Akun swalayan SDM FKIP Unsil telah dibuat untuk Anda.')
            ->line('Silakan atur kata sandi Anda melalui tautan berikut.')
            ->action('Atur kata sandi', $this->urlAturKataSandi)
            ->line('Tautan ini berlaku terbatas. Abaikan surel ini bila Anda tidak merasa memintanya.');
    }
}

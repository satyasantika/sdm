<?php

namespace App\Notifications;

use App\Enums\StatusCekTautan;
use App\Models\TautanBerkas;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TautanBerkasBermasalah extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly TautanBerkas $tautan, public readonly StatusCekTautan $status)
    {
        $this->onQueue('notifikasi');
    }

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    private function pesan(): string
    {
        $nama = $this->tautan->label ?: $this->tautan->jenis->getLabel();

        return match ($this->status) {
            StatusCekTautan::TerlaluTerbuka => "Berkas \"{$nama}\" dapat dibuka siapa saja. Ubah izin berbagi menjadi terbatas (domain unsil.ac.id).",
            default => "Berkas \"{$nama}\" tidak dapat diakses. Periksa tautan atau izin berbaginya.",
        };
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Tautan berkas bermasalah')
            ->line($this->pesan())
            ->line('Pemeriksaan dilakukan otomatis tanpa login; status ini bersifat informatif.');
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Tautan berkas bermasalah',
            'body' => $this->pesan(),
            'tautan_berkas_id' => $this->tautan->getKey(),
            'status' => $this->status->value,
        ];
    }
}

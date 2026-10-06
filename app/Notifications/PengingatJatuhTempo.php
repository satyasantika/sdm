<?php

namespace App\Notifications;

use App\Filament\Admin\Resources\Pengingat\PengingatResource;
use App\Filament\Swalayan\Pages\Beranda;
use App\Models\Pengingat;
use App\Models\User;
use App\Notifications\Channels\WhatsAppChannel;
use Filament\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Pengingat tenggat untuk pegawai bersangkutan. Tanpa NIK/NIP lengkap atau data sensitif. */
class PengingatJatuhTempo extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly Pengingat $pengingat, public readonly bool $untukPegawai = true)
    {
        $this->onQueue('notifikasi');
    }

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        $channel = ['database', 'mail'];

        if (config('services.whatsapp.enabled')) {
            $channel[] = WhatsAppChannel::class;
        }

        return $channel;
    }

    private function url(): string
    {
        return $this->untukPegawai ? Beranda::getUrl(panel: 'swalayan') : PengingatResource::getUrl('index');
    }

    public function pesan(): string
    {
        $p = $this->pengingat;
        $nama = $p->pegawai->nama_bergelar;
        $tanggal = $p->tanggal_jatuh_tempo->translatedFormat('d F Y');

        return "{$p->jenis->getLabel()} untuk {$nama}: jatuh tempo {$tanggal} ({$p->sisaHariLabel()}).";
    }

    /** @return array<string, mixed> */
    public function toDatabase(object $notifiable): array
    {
        return FilamentNotification::make()
            ->title('Pengingat: '.$this->pengingat->jenis->getLabel())
            ->body($this->pesan())
            ->icon('heroicon-o-bell-alert')
            ->actions([Action::make('lihat')->label('Lihat')->url($this->url())])
            ->getDatabaseMessage();
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Pengingat: '.$this->pengingat->jenis->getLabel())
            ->line($this->pesan())
            ->action('Buka SDM FKIP', $this->url());
    }

    /** @return array{tujuan: string|null, pesan: string} */
    public function toWhatsApp(object $notifiable): array
    {
        $nomor = $notifiable instanceof User ? ($notifiable->no_hp ?: $notifiable->pegawai?->no_hp) : null;

        return ['tujuan' => $nomor, 'pesan' => "SDM FKIP Unsil — {$this->pesan()}"];
    }
}

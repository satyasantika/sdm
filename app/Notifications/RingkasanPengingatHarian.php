<?php

namespace App\Notifications;

use App\Filament\Admin\Resources\Pengingat\PengingatResource;
use App\Models\Pengingat;
use Filament\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;

/** Satu ringkasan harian untuk admin (bukan satu notifikasi per pegawai). */
class RingkasanPengingatHarian extends Notification implements ShouldQueue
{
    use Queueable;

    /** @param  Collection<int, Pengingat>  $pengingat */
    public function __construct(public readonly Collection $pengingat, public readonly ?string $namaProdi = null)
    {
        $this->onQueue('notifikasi');
    }

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    /** @return list<string> */
    public function baris(): array
    {
        return $this->pengingat->take(20)->map(fn (Pengingat $p): string => "{$p->pegawai->nama_bergelar} — {$p->jenis->getLabel()} ({$p->tanggal_jatuh_tempo->translatedFormat('d F Y')}, {$p->sisaHariLabel()})")->all();
    }

    private function judul(): string
    {
        return 'Ringkasan pengingat harian'.($this->namaProdi ? " — {$this->namaProdi}" : '');
    }

    /** @return array<string, mixed> */
    public function toDatabase(object $notifiable): array
    {
        return FilamentNotification::make()
            ->title($this->judul())
            ->body($this->pengingat->count().' pengingat jatuh tempo bertahap hari ini.')
            ->icon('heroicon-o-bell-alert')
            ->actions([Action::make('lihat')->label('Lihat pengingat')->url(PengingatResource::getUrl('index'))])
            ->getDatabaseMessage();
    }

    public function toMail(object $notifiable): MailMessage
    {
        $surel = (new MailMessage)->subject($this->judul())->line($this->pengingat->count().' pengingat jatuh tempo bertahap hari ini:');

        foreach ($this->baris() as $baris) {
            $surel->line('- '.$baris);
        }

        return $surel->action('Buka daftar pengingat', PengingatResource::getUrl('index'));
    }
}

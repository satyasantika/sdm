<?php

namespace App\Notifications\Channels;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/** Channel notifikasi WhatsApp via gateway HTTP (mis. Fonnte). Dikirim lewat antrean; gagal → exception agar dicoba ulang. */
class WhatsAppChannel
{
    public function send(object $notifiable, Notification $notification): void
    {
        if (! config('services.whatsapp.enabled') || ! method_exists($notification, 'toWhatsApp')) {
            return;
        }

        $pesan = $notification->toWhatsApp($notifiable);
        $tujuan = self::normalisasi($pesan['tujuan'] ?? null);

        if ($tujuan === null) {
            return;
        }

        $respons = Http::withHeaders(['Authorization' => (string) config('services.whatsapp.token')])
            ->timeout(10)
            ->asForm()
            ->post((string) config('services.whatsapp.endpoint'), ['target' => $tujuan, 'message' => $pesan['pesan']]);

        if (! $respons->successful()) {
            // Token tidak pernah dicatat; hanya kode status.
            throw new RuntimeException('Gateway WhatsApp gagal: HTTP '.$respons->status());
        }
    }

    /** 0812… / +62812… / 62812… → 62812… */
    public static function normalisasi(?string $nomor): ?string
    {
        $digit = preg_replace('/\D/', '', (string) $nomor);

        if ($digit === null || $digit === '') {
            return null;
        }

        return match (true) {
            str_starts_with($digit, '62') => $digit,
            str_starts_with($digit, '0') => '62'.substr($digit, 1),
            default => '62'.$digit,
        };
    }
}

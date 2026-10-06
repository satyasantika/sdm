<?php

namespace App\Support;

use Closure;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\RateLimiter;

/** Pembatas ekspor 5 permintaan per menit per pengguna (untuk hook ->before() pada aksi ekspor). */
class BatasEkspor
{
    public const MAKS = 5;

    public static function sebelum(): Closure
    {
        return function (Action $action): void {
            $kunci = 'ekspor:'.auth()->id();

            if (RateLimiter::tooManyAttempts($kunci, self::MAKS)) {
                Notification::make()->warning()->title('Terlalu banyak permintaan ekspor. Coba lagi sebentar.')->send();
                $action->halt();

                return;
            }

            RateLimiter::hit($kunci, 60);
        };
    }
}

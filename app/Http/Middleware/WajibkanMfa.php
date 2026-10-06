<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Peran super-admin dan admin-kepegawaian wajib mengaktifkan MFA (TOTP)
 * sebelum memakai panel; sisanya diarahkan ke halaman profil.
 */
class WajibkanMfa
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof User || ! $user->wajibMfa() || filled($user->getAppAuthenticationSecret())) {
            return $next($request);
        }

        $profil = Filament::getProfileUrl();

        if ($profil === null || $request->url() === $profil || $request->routeIs('filament.*.auth.*')) {
            return $next($request);
        }

        Notification::make()
            ->title('Aktifkan autentikasi dua faktor')
            ->body('Peran Anda wajib mengaktifkan MFA (aplikasi autentikator) sebelum melanjutkan.')
            ->warning()
            ->send();

        return redirect()->to($profil);
    }
}

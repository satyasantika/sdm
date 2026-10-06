<?php

namespace App\Actions\Pegawai;

use App\Models\Pegawai;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use InvalidArgumentException;

/** Menampilkan nilai polos NIK/NPWP/rekening dengan otorisasi, rate limit 10/menit, dan log akses (BR-16). */
class TampilkanDataSensitif
{
    public const KOLOM_DIIZINKAN = ['nik', 'npwp', 'nomor_rekening'];

    public const BATAS_PER_MENIT = 10;

    public function handle(User $user, Pegawai $pegawai, string $kolom): string
    {
        if (! in_array($kolom, self::KOLOM_DIIZINKAN, true)) {
            throw new InvalidArgumentException("Kolom [{$kolom}] tidak termasuk data sensitif yang dapat ditampilkan.");
        }

        if (! Gate::forUser($user)->allows('viewSensitive', $pegawai)) {
            throw new AuthorizationException('Anda tidak berwenang melihat data sensitif ini.');
        }

        $kunci = 'tampil-sensitif:'.$user->getKey();
        if (RateLimiter::tooManyAttempts($kunci, self::BATAS_PER_MENIT)) {
            throw new ThrottleRequestsException('Terlalu banyak permintaan tampil data sensitif. Coba lagi sebentar.');
        }
        RateLimiter::hit($kunci, 60);

        activity('akses-sensitif')
            ->performedOn($pegawai)
            ->causedBy($user)
            ->withProperties(['kolom' => $kolom, 'ip' => request()->ip(), 'user_agent' => request()->userAgent()])
            ->log('tampil');

        return (string) $pegawai->getAttribute($kolom);
    }
}

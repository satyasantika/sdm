<?php

namespace App\Actions\Usulan;

use App\Models\User;
use App\Models\UsulanPerubahan;
use App\Support\RegistriTargetUsulan;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Support\Facades\RateLimiter;

/** Menampilkan nilai lama/baru kolom sensitif sebuah usulan secara penuh; berizin, rate limit, dan tercatat (BR-16). */
class TampilkanDataUsulanSensitif
{
    /** @return array<string, array{lama: mixed, baru: mixed}> */
    public function handle(User $user, UsulanPerubahan $usulan): array
    {
        if (! $user->can('pegawai.lihat-sensitif')) {
            throw new AuthorizationException('Anda tidak berwenang melihat data sensitif ini.');
        }

        $kunci = 'tampil-sensitif:'.$user->getKey();
        if (RateLimiter::tooManyAttempts($kunci, 10)) {
            throw new ThrottleRequestsException('Terlalu banyak permintaan tampil data sensitif. Coba lagi sebentar.');
        }
        RateLimiter::hit($kunci, 60);

        $hasil = [];
        foreach (RegistriTargetUsulan::kolomSensitif($usulan->target_tabel) as $kolom) {
            if (array_key_exists($kolom, $usulan->data_baru ?? []) || array_key_exists($kolom, $usulan->data_lama ?? [])) {
                $hasil[$kolom] = ['lama' => $usulan->data_lama[$kolom] ?? null, 'baru' => $usulan->data_baru[$kolom] ?? null];
            }
        }

        activity('akses-sensitif')
            ->performedOn($usulan)
            ->causedBy($user)
            ->withProperties(['kolom' => implode(',', array_keys($hasil)), 'sumber' => 'usulan', 'ip' => request()->ip(), 'user_agent' => request()->userAgent()])
            ->log('tampil');

        return $hasil;
    }
}

<?php

namespace App\Policies;

use App\Enums\Peran;
use App\Models\TautanBerkas;
use App\Models\User;

class TautanBerkasPolicy
{
    /** BR-30: URL tautan sensitif hanya untuk pemilik, admin-kepegawaian, super-admin, dan pemegang tautan-sensitif.lihat. */
    public function buka(User $user, TautanBerkas $tautan): bool
    {
        if ($user->hasAnyRole([Peran::SuperAdmin->value, Peran::AdminKepegawaian->value])) {
            return true;
        }

        $pegawai = $tautan->pegawai;

        if ($pegawai?->user_id !== null && $pegawai->user_id === $user->getKey()) {
            return true;
        }

        if (! $tautan->is_sensitif) {
            return $this->nonSensitif($user, $tautan);
        }

        // pimpinan dan admin-prodi hanya melihat status ada/tidaknya berkas sensitif (BR-30).
        if ($user->hasAnyRole([Peran::Pimpinan->value, Peran::AdminProdi->value])) {
            return false;
        }

        return $user->can('tautan-sensitif.lihat');
    }

    private function nonSensitif(User $user, TautanBerkas $tautan): bool
    {
        if ($user->hasRole(Peran::AdminProdi->value)) {
            return $user->prodi_id !== null && $tautan->pegawai?->prodi_id === $user->prodi_id;
        }

        return $user->hasRole(Peran::Pimpinan->value) || $user->can('tautan-sensitif.lihat');
    }
}

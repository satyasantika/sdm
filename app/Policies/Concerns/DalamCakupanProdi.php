<?php

namespace App\Policies\Concerns;

use App\Enums\JenisPegawai;
use App\Enums\Peran;
use App\Models\Pegawai;
use App\Models\User;

trait DalamCakupanProdi
{
    /** BR-03: admin-prodi hanya pegawai (dosen) homebase prodinya. */
    protected function dalamCakupan(User $user, ?Pegawai $pegawai): bool
    {
        if ($pegawai === null) {
            return false;
        }

        if (! $user->hasRole(Peran::AdminProdi->value) || $user->hasAnyRole([Peran::SuperAdmin->value, Peran::AdminKepegawaian->value])) {
            return true;
        }

        return $user->prodi_id !== null
            && $pegawai->prodi_id === $user->prodi_id
            && $pegawai->jenis_pegawai === JenisPegawai::Dosen;
    }

    protected function adminPenuh(User $user): bool
    {
        return $user->hasAnyRole([Peran::SuperAdmin->value, Peran::AdminKepegawaian->value]);
    }
}

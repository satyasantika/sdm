<?php

namespace App\Policies;

use App\Models\User;
use App\Models\UsulanPerubahan;
use App\Policies\Concerns\DalamCakupanProdi;

class UsulanPerubahanPolicy
{
    use DalamCakupanProdi;

    public function viewAny(User $user): bool
    {
        return $user->canAny(['usulan.lihat', 'usulan.verifikasi']);
    }

    /** Pemilik, verifikator, atau admin-prodi untuk pegawai prodinya. */
    public function view(User $user, UsulanPerubahan $usulan): bool
    {
        if ($usulan->diajukan_oleh === $user->getKey()) {
            return true;
        }

        if ($user->can('usulan.verifikasi')) {
            return true;
        }

        return $user->can('usulan.lihat') && $this->dalamCakupan($user, $usulan->pegawai);
    }

    public function create(User $user): bool
    {
        return $user->can('usulan.ajukan') && $user->pegawai !== null;
    }

    public function verifikasi(User $user, UsulanPerubahan $usulan): bool
    {
        return $user->can('usulan.verifikasi');
    }

    public function update(User $user, UsulanPerubahan $usulan): bool
    {
        return $usulan->diajukan_oleh === $user->getKey() && $usulan->status->isAktif();
    }

    public function delete(User $user, UsulanPerubahan $usulan): bool
    {
        return false;
    }
}

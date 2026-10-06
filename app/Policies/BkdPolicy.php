<?php

namespace App\Policies;

use App\Models\Bkd;
use App\Models\Pegawai;
use App\Models\User;
use App\Policies\Concerns\DalamCakupanProdi;

class BkdPolicy
{
    use DalamCakupanProdi;

    public function viewAny(User $user): bool
    {
        return $user->can('bkd.lihat');
    }

    public function view(User $user, Bkd $bkd): bool
    {
        $pegawai = Pegawai::withTrashed()->find($bkd->pegawai_id);

        if ($pegawai?->user_id !== null && $pegawai->user_id === $user->getKey()) {
            return true;
        }

        return $user->can('bkd.lihat') && $this->dalamCakupan($user, $pegawai);
    }

    public function create(User $user): bool
    {
        return $user->can('bkd.impor');
    }

    public function update(User $user, Bkd $bkd): bool
    {
        return $user->can('bkd.impor');
    }

    /** Hanya super-admin (lewat Gate::before). */
    public function delete(User $user, Bkd $bkd): bool
    {
        return false;
    }
}

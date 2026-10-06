<?php

namespace App\Policies;

use App\Models\Pegawai;
use App\Models\Pengingat;
use App\Models\User;
use App\Policies\Concerns\DalamCakupanProdi;

class PengingatPolicy
{
    use DalamCakupanProdi;

    public function viewAny(User $user): bool
    {
        return $user->can('pengingat.lihat');
    }

    public function view(User $user, Pengingat $pengingat): bool
    {
        $pegawai = Pegawai::withTrashed()->find($pengingat->pegawai_id);

        if ($pegawai?->user_id !== null && $pegawai->user_id === $user->getKey()) {
            return true;
        }

        return $user->can('pengingat.lihat') && $this->dalamCakupan($user, $pegawai);
    }

    public function update(User $user, Pengingat $pengingat): bool
    {
        return $user->can('pengingat.kelola');
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function delete(User $user, Pengingat $pengingat): bool
    {
        return false;
    }
}

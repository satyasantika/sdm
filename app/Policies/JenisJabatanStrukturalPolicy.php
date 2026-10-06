<?php

namespace App\Policies;

use App\Models\JenisJabatanStruktural;
use App\Models\User;

class JenisJabatanStrukturalPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('master.lihat');
    }

    public function view(User $user, JenisJabatanStruktural $model): bool
    {
        return $user->can('master.lihat');
    }

    public function create(User $user): bool
    {
        return $user->can('master.kelola');
    }

    public function update(User $user, JenisJabatanStruktural $model): bool
    {
        return $user->can('master.kelola');
    }

    public function delete(User $user, JenisJabatanStruktural $model): bool
    {
        return $user->can('master.kelola');
    }

    public function restore(User $user, JenisJabatanStruktural $model): bool
    {
        return $user->can('master.kelola');
    }

    public function forceDelete(User $user, JenisJabatanStruktural $model): bool
    {
        return false; // hanya super-admin lewat Gate::before
    }
}

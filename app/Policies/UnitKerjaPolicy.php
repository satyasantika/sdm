<?php

namespace App\Policies;

use App\Models\UnitKerja;
use App\Models\User;

class UnitKerjaPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('master.lihat');
    }

    public function view(User $user, UnitKerja $model): bool
    {
        return $user->can('master.lihat');
    }

    public function create(User $user): bool
    {
        return $user->can('master.kelola');
    }

    public function update(User $user, UnitKerja $model): bool
    {
        return $user->can('master.kelola');
    }

    public function delete(User $user, UnitKerja $model): bool
    {
        return $user->can('master.kelola');
    }

    public function restore(User $user, UnitKerja $model): bool
    {
        return $user->can('master.kelola');
    }

    public function forceDelete(User $user, UnitKerja $model): bool
    {
        return false; // hanya super-admin lewat Gate::before
    }
}

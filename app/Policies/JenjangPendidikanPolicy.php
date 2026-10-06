<?php

namespace App\Policies;

use App\Models\JenjangPendidikan;
use App\Models\User;

class JenjangPendidikanPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('master.lihat');
    }

    public function view(User $user, JenjangPendidikan $model): bool
    {
        return $user->can('master.lihat');
    }

    public function create(User $user): bool
    {
        return $user->can('master.kelola');
    }

    public function update(User $user, JenjangPendidikan $model): bool
    {
        return $user->can('master.kelola');
    }

    public function delete(User $user, JenjangPendidikan $model): bool
    {
        return $user->can('master.kelola');
    }

    public function restore(User $user, JenjangPendidikan $model): bool
    {
        return $user->can('master.kelola');
    }

    public function forceDelete(User $user, JenjangPendidikan $model): bool
    {
        return false; // hanya super-admin lewat Gate::before
    }
}

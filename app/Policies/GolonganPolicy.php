<?php

namespace App\Policies;

use App\Models\Golongan;
use App\Models\User;

class GolonganPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('master.lihat');
    }

    public function view(User $user, Golongan $model): bool
    {
        return $user->can('master.lihat');
    }

    public function create(User $user): bool
    {
        return $user->can('master.kelola');
    }

    public function update(User $user, Golongan $model): bool
    {
        return $user->can('master.kelola');
    }

    public function delete(User $user, Golongan $model): bool
    {
        return $user->can('master.kelola');
    }

    public function restore(User $user, Golongan $model): bool
    {
        return $user->can('master.kelola');
    }

    public function forceDelete(User $user, Golongan $model): bool
    {
        return false; // hanya super-admin lewat Gate::before
    }
}

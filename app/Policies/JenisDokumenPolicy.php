<?php

namespace App\Policies;

use App\Models\JenisDokumen;
use App\Models\User;

class JenisDokumenPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('master.lihat');
    }

    public function view(User $user, JenisDokumen $model): bool
    {
        return $user->can('master.lihat');
    }

    public function create(User $user): bool
    {
        return $user->can('master.kelola');
    }

    public function update(User $user, JenisDokumen $model): bool
    {
        return $user->can('master.kelola');
    }

    public function delete(User $user, JenisDokumen $model): bool
    {
        return $user->can('master.kelola');
    }

    public function restore(User $user, JenisDokumen $model): bool
    {
        return $user->can('master.kelola');
    }

    public function forceDelete(User $user, JenisDokumen $model): bool
    {
        return false; // hanya super-admin lewat Gate::before
    }
}

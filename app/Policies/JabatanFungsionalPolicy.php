<?php

namespace App\Policies;

use App\Models\JabatanFungsional;
use App\Models\User;

class JabatanFungsionalPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('master.lihat');
    }

    public function view(User $user, JabatanFungsional $model): bool
    {
        return $user->can('master.lihat');
    }

    public function create(User $user): bool
    {
        return $user->can('master.kelola');
    }

    public function update(User $user, JabatanFungsional $model): bool
    {
        return $user->can('master.kelola');
    }

    public function delete(User $user, JabatanFungsional $model): bool
    {
        return $user->can('master.kelola');
    }

    public function restore(User $user, JabatanFungsional $model): bool
    {
        return $user->can('master.kelola');
    }

    public function forceDelete(User $user, JabatanFungsional $model): bool
    {
        return false; // hanya super-admin lewat Gate::before
    }
}

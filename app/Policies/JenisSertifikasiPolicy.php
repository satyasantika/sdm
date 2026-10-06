<?php

namespace App\Policies;

use App\Models\JenisSertifikasi;
use App\Models\User;

class JenisSertifikasiPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('master.lihat');
    }

    public function view(User $user, JenisSertifikasi $model): bool
    {
        return $user->can('master.lihat');
    }

    public function create(User $user): bool
    {
        return $user->can('master.kelola');
    }

    public function update(User $user, JenisSertifikasi $model): bool
    {
        return $user->can('master.kelola');
    }

    public function delete(User $user, JenisSertifikasi $model): bool
    {
        return $user->can('master.kelola');
    }

    public function restore(User $user, JenisSertifikasi $model): bool
    {
        return $user->can('master.kelola');
    }

    public function forceDelete(User $user, JenisSertifikasi $model): bool
    {
        return false; // hanya super-admin lewat Gate::before
    }
}

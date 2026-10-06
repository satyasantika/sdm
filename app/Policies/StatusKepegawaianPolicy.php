<?php

namespace App\Policies;

use App\Models\StatusKepegawaian;
use App\Models\User;

class StatusKepegawaianPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('master.lihat');
    }

    public function view(User $user, StatusKepegawaian $model): bool
    {
        return $user->can('master.lihat');
    }

    public function create(User $user): bool
    {
        return $user->can('master.kelola');
    }

    public function update(User $user, StatusKepegawaian $model): bool
    {
        return $user->can('master.kelola');
    }

    public function delete(User $user, StatusKepegawaian $model): bool
    {
        return $user->can('master.kelola');
    }

    public function restore(User $user, StatusKepegawaian $model): bool
    {
        return $user->can('master.kelola');
    }

    public function forceDelete(User $user, StatusKepegawaian $model): bool
    {
        return false; // hanya super-admin lewat Gate::before
    }
}

<?php

namespace App\Policies;

use App\Models\Semester;
use App\Models\User;

class SemesterPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('master.lihat');
    }

    public function view(User $user, Semester $model): bool
    {
        return $user->can('master.lihat');
    }

    public function create(User $user): bool
    {
        return $user->can('master.kelola');
    }

    public function update(User $user, Semester $model): bool
    {
        return $user->can('master.kelola');
    }

    public function delete(User $user, Semester $model): bool
    {
        return $user->can('master.kelola');
    }
}

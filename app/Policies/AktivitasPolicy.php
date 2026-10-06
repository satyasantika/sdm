<?php

namespace App\Policies;

use App\Models\Aktivitas;
use App\Models\User;

class AktivitasPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('audit.lihat');
    }

    public function view(User $user, Aktivitas $aktivitas): bool
    {
        return $user->can('audit.lihat');
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Aktivitas $aktivitas): bool
    {
        return false;
    }

    public function delete(User $user, Aktivitas $aktivitas): bool
    {
        return false;
    }
}

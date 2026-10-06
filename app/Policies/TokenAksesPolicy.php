<?php

namespace App\Policies;

use App\Models\TokenAkses;
use App\Models\User;

class TokenAksesPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('api.kelola-token');
    }

    public function view(User $user, TokenAkses $model): bool
    {
        return $user->can('api.kelola-token');
    }

    public function create(User $user): bool
    {
        return $user->can('api.kelola-token');
    }

    public function delete(User $user, TokenAkses $model): bool
    {
        return $user->can('api.kelola-token');
    }
}

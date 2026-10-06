<?php

namespace App\Policies;

use App\Models\Keluarga;
use App\Models\Pegawai;
use App\Models\User;

class KeluargaPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('keluarga.lihat');
    }

    public function view(User $user, Keluarga $keluarga): bool
    {
        return $user->can('keluarga.lihat') || $this->pemilik($user, $keluarga);
    }

    public function create(User $user): bool
    {
        return $user->can('keluarga.kelola');
    }

    public function update(User $user, Keluarga $keluarga): bool
    {
        return $user->can('keluarga.kelola');
    }

    public function delete(User $user, Keluarga $keluarga): bool
    {
        return $user->can('keluarga.kelola');
    }

    private function pemilik(User $user, Keluarga $keluarga): bool
    {
        $pegawai = Pegawai::withTrashed()->find($keluarga->pegawai_id);

        return $pegawai?->user_id !== null && $pegawai->user_id === $user->getKey();
    }
}

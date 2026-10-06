<?php

namespace App\Policies;

use App\Enums\Peran;
use App\Models\JumlahMahasiswaProdi;
use App\Models\User;

/** Diinput admin-kepegawaian dan admin-prodi (prodinya saja). */
class JumlahMahasiswaProdiPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canAny(['laporan.lihat', 'bkd.impor']) || $user->hasRole(Peran::AdminProdi->value);
    }

    public function view(User $user, JumlahMahasiswaProdi $data): bool
    {
        return $this->viewAny($user) && $this->dalamCakupan($user, $data->prodi_id);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole([Peran::SuperAdmin->value, Peran::AdminKepegawaian->value, Peran::AdminProdi->value]);
    }

    public function update(User $user, JumlahMahasiswaProdi $data): bool
    {
        return $this->create($user) && $this->dalamCakupan($user, $data->prodi_id);
    }

    public function delete(User $user, JumlahMahasiswaProdi $data): bool
    {
        return $user->hasAnyRole([Peran::SuperAdmin->value, Peran::AdminKepegawaian->value]);
    }

    private function dalamCakupan(User $user, string $prodiId): bool
    {
        if ($user->hasAnyRole([Peran::SuperAdmin->value, Peran::AdminKepegawaian->value, Peran::Pimpinan->value])) {
            return true;
        }

        return $user->prodi_id !== null && $user->prodi_id === $prodiId;
    }
}

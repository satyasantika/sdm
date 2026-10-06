<?php

namespace App\Policies;

use App\Enums\JenisPegawai;
use App\Enums\Peran;
use App\Models\Pegawai;
use App\Models\User;

class PegawaiPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('pegawai.lihat');
    }

    public function view(User $user, Pegawai $pegawai): bool
    {
        return $user->can('pegawai.lihat') && $this->dalamCakupan($user, $pegawai);
    }

    public function create(User $user): bool
    {
        return $user->can('pegawai.buat');
    }

    public function update(User $user, Pegawai $pegawai): bool
    {
        return $user->can('pegawai.ubah') && $this->dalamCakupan($user, $pegawai);
    }

    /** BR-23: pegawai tidak dihapus; hanya super-admin (lewat Gate::before). */
    public function delete(User $user, Pegawai $pegawai): bool
    {
        return false;
    }

    public function restore(User $user, Pegawai $pegawai): bool
    {
        return false;
    }

    public function forceDelete(User $user, Pegawai $pegawai): bool
    {
        return false;
    }

    public function viewSensitive(User $user, Pegawai $pegawai): bool
    {
        return $user->can('pegawai.lihat-sensitif') || $this->milikSendiri($user, $pegawai);
    }

    public function ubahStatus(User $user, Pegawai $pegawai): bool
    {
        return $this->update($user, $pegawai);
    }

    private function milikSendiri(User $user, Pegawai $pegawai): bool
    {
        return $pegawai->user_id !== null && $pegawai->user_id === $user->getKey();
    }

    /** BR-03: admin-prodi hanya pegawai prodinya (dosen). */
    private function dalamCakupan(User $user, Pegawai $pegawai): bool
    {
        if (! $user->hasRole(Peran::AdminProdi->value) || $user->hasAnyRole([Peran::SuperAdmin->value, Peran::AdminKepegawaian->value])) {
            return true;
        }

        return $user->prodi_id !== null
            && $pegawai->prodi_id === $user->prodi_id
            && $pegawai->jenis_pegawai === JenisPegawai::Dosen;
    }
}

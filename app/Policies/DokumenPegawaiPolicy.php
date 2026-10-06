<?php

namespace App\Policies;

use App\Models\DokumenPegawai;
use App\Models\Pegawai;
use App\Models\User;
use App\Policies\Concerns\DalamCakupanProdi;

class DokumenPegawaiPolicy
{
    use DalamCakupanProdi;

    public function viewAny(User $user): bool
    {
        return $user->can('dokumen.lihat');
    }

    /** Dokumen identitas hanya untuk admin kepegawaian/super-admin/pemilik; selain itu dokumen.lihat + cakupan prodi. */
    public function view(User $user, DokumenPegawai $dokumen): bool
    {
        $pegawai = Pegawai::withTrashed()->find($dokumen->pegawai_id);

        if ($pegawai?->user_id !== null && $pegawai->user_id === $user->getKey()) {
            return true;
        }

        if ($dokumen->jenisDokumen->is_identitas && ! $this->adminPenuh($user)) {
            return false;
        }

        return $user->can('dokumen.lihat') && $this->dalamCakupan($user, $pegawai);
    }

    public function create(User $user): bool
    {
        return $user->can('dokumen.tambah');
    }

    public function update(User $user, DokumenPegawai $dokumen): bool
    {
        return $user->can('dokumen.kelola') && $this->dalamCakupan($user, Pegawai::withTrashed()->find($dokumen->pegawai_id));
    }

    public function delete(User $user, DokumenPegawai $dokumen): bool
    {
        return $this->update($user, $dokumen);
    }
}

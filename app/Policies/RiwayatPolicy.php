<?php

namespace App\Policies;

use App\Models\Pegawai;
use App\Models\User;
use App\Policies\Concerns\DalamCakupanProdi;
use Illuminate\Database\Eloquent\Model;

/**
 * Dasar Policy riwayat: lihat → riwayat.lihat + cakupan prodi; tulis → izin kelola + cakupan prodi;
 * hapus → admin-kepegawaian/super-admin.
 */
abstract class RiwayatPolicy
{
    use DalamCakupanProdi;

    /** Izin untuk menulis (riwayat-akademik.kelola atau riwayat-kepegawaian.kelola). */
    abstract protected function izinKelola(): string;

    public function viewAny(User $user): bool
    {
        return $user->can('riwayat.lihat');
    }

    public function view(User $user, Model $riwayat): bool
    {
        $pegawai = $this->pegawaiDari($riwayat);

        // Pemilik data selalu boleh melihat riwayatnya sendiri (perubahan lewat usulan di F6).
        if ($pegawai?->user_id !== null && $pegawai->user_id === $user->getKey()) {
            return true;
        }

        return $user->can('riwayat.lihat') && $this->dalamCakupan($user, $pegawai);
    }

    public function create(User $user): bool
    {
        return $user->can($this->izinKelola());
    }

    public function update(User $user, Model $riwayat): bool
    {
        return $user->can($this->izinKelola()) && $this->dalamCakupan($user, $this->pegawaiDari($riwayat));
    }

    public function delete(User $user, Model $riwayat): bool
    {
        return $this->adminPenuh($user) && $this->dalamCakupan($user, $this->pegawaiDari($riwayat));
    }

    protected function pegawaiDari(Model $riwayat): ?Pegawai
    {
        $pegawai = $riwayat->getAttribute('pegawai_id') ? Pegawai::withTrashed()->find($riwayat->getAttribute('pegawai_id')) : null;

        return $pegawai;
    }
}

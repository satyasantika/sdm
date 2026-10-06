<?php

namespace App\Support;

use App\Enums\Peran;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/** Pembatasan data per prodi untuk admin-prodi (BR-03), dipakai kueri ekspor yang tidak melewati Policy. */
class CakupanProdi
{
    /** Id prodi yang mengunci pengguna; null bila tidak dibatasi. Admin-prodi tanpa prodi memperoleh id kosong (tanpa data). */
    public static function terkunci(?User $user): ?string
    {
        if ($user && $user->hasRole(Peran::AdminProdi->value) && ! $user->hasAnyRole([Peran::SuperAdmin->value, Peran::AdminKepegawaian->value])) {
            return $user->prodi_id ?? '00000000-0000-0000-0000-000000000000';
        }

        return null;
    }

    /**
     * @param  Builder<Model>  $query
     * @param  string|null  $relasiPegawai  nama relasi ke pegawai; null bila query adalah pegawai itu sendiri
     * @return Builder<Model>
     */
    public static function batasi(Builder $query, ?User $user, ?string $relasiPegawai = 'pegawai'): Builder
    {
        $prodi = self::terkunci($user);

        if ($prodi === null) {
            return $query;
        }

        return $relasiPegawai === null
            ? $query->where('prodi_id', $prodi)
            : $query->whereHas($relasiPegawai, fn (Builder $q) => $q->where('prodi_id', $prodi));
    }
}

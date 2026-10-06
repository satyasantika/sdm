<?php

namespace App\Support;

use App\Enums\KesimpulanBkd;
use App\Enums\Peran;
use App\Models\Bkd;
use App\Models\Pegawai;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/** Ringkasan BKD per semester/prodi: dosen tetap, memenuhi, tidak memenuhi, dan dosen tetap tanpa data BKD. */
class BkdRingkasan
{
    /** @return Builder<Pegawai> */
    public static function dosenTetap(?string $prodiId, ?User $user = null): Builder
    {
        $query = Pegawai::query()->dosenTetap();

        if ($user && $user->hasRole(Peran::AdminProdi->value) && ! $user->hasAnyRole([Peran::SuperAdmin->value, Peran::AdminKepegawaian->value])) {
            $user->prodi_id ? $query->where('prodi_id', $user->prodi_id) : $query->whereRaw('1 = 0');
        }

        return $query->when($prodiId, fn (Builder $q) => $q->where('prodi_id', $prodiId));
    }

    /** @return Builder<Pegawai> dosen tetap tanpa baris BKD pada semester itu */
    public static function tanpaData(?string $semesterId, ?string $prodiId, ?User $user = null): Builder
    {
        return self::dosenTetap($prodiId, $user)
            ->whereDoesntHave('bkd', fn (Builder $q) => $semesterId ? $q->where('semester_id', $semesterId) : $q->whereRaw('1 = 0'));
    }

    /** @return array{dosen_tetap: int, memenuhi: int, tidak_memenuhi: int, belum_ada_data: int} */
    public static function hitung(?string $semesterId, ?string $prodiId, ?User $user = null): array
    {
        $bkd = fn (KesimpulanBkd $k): int => Bkd::query()
            ->where('semester_id', $semesterId)
            ->where('kesimpulan', $k->value)
            ->whereHas('pegawai', function (Builder $q) use ($prodiId, $user): void {
                $q->whereIn('id', self::dosenTetap($prodiId, $user)->select('id'));
            })
            ->count();

        return [
            'dosen_tetap' => self::dosenTetap($prodiId, $user)->count(),
            'memenuhi' => $bkd(KesimpulanBkd::Memenuhi),
            'tidak_memenuhi' => $bkd(KesimpulanBkd::TidakMemenuhi),
            'belum_ada_data' => self::tanpaData($semesterId, $prodiId, $user)->count(),
        ];
    }
}

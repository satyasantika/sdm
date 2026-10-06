<?php

namespace App\Support;

use App\Models\Konfigurasi as KonfigurasiModel;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

/**
 * Akses konfigurasi kepegawaian dari cache (kunci sdm:konfigurasi). Angka regulasi
 * (BUP, interval, syarat jabatan) selalu dibaca lewat kelas ini, tidak pernah di-hard-code.
 */
class Konfigurasi
{
    public const KUNCI_CACHE = 'sdm:konfigurasi';

    public static function get(string $kunci, mixed $bawaan = null): mixed
    {
        $semua = self::semua();

        return array_key_exists($kunci, $semua) ? $semua[$kunci] : $bawaan;
    }

    /** @return array<string, mixed> */
    public static function semua(): array
    {
        return Cache::remember(self::KUNCI_CACHE, 86400, fn (): array => KonfigurasiModel::query()
            ->get()
            ->mapWithKeys(fn (KonfigurasiModel $k): array => [$k->kunci => $k->nilai_terketik])
            ->all());
    }

    public static function set(string $kunci, mixed $nilai, ?User $oleh = null): void
    {
        $baris = KonfigurasiModel::firstOrNew(['kunci' => $kunci]);
        $baris->fill([
            'nilai' => KonfigurasiModel::serialisasi($nilai),
            'tipe' => $baris->exists ? $baris->tipe : KonfigurasiModel::tipeDari($nilai),
            'diubah_oleh' => $oleh?->getKey(),
        ])->save();
    }

    public static function lupakan(): void
    {
        Cache::forget(self::KUNCI_CACHE);
    }
}

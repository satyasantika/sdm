<?php

namespace App\Models\Concerns;

/**
 * Menandai satu baris is_terkini per pegawai: baris dengan tanggal mulai (kolom TMT) terbaru.
 * Model dapat menimpa kolomTmt().
 */
trait PunyaRiwayatTerkini
{
    protected static function kolomTmt(): string
    {
        return 'tmt';
    }

    public static function tandaiTerkini(string $pegawaiId): ?static
    {
        $kolom = static::kolomTmt();

        static::query()->where('pegawai_id', $pegawaiId)->where('is_terkini', true)->update(['is_terkini' => false]);

        /** @var static|null $terkini */
        $terkini = static::query()->where('pegawai_id', $pegawaiId)
            ->orderByDesc($kolom)->orderByDesc('created_at')->first();

        $terkini?->forceFill(['is_terkini' => true])->saveQuietly();

        return $terkini;
    }
}

<?php

namespace App\Observers;

use Illuminate\Support\Facades\Cache;

/** Menghapus cache sdm:master:{nama} saat model master berubah (didaftarkan di AppServiceProvider). */
class MasterCacheObserver
{
    public static function lupakan(string $nama): void
    {
        Cache::forget('sdm:master:'.$nama);
    }
}

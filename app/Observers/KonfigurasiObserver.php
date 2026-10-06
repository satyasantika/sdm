<?php

namespace App\Observers;

use App\Models\Konfigurasi;
use App\Support\Konfigurasi as KonfigurasiCache;

class KonfigurasiObserver
{
    public function saved(Konfigurasi $konfigurasi): void
    {
        KonfigurasiCache::lupakan();
    }

    public function deleted(Konfigurasi $konfigurasi): void
    {
        KonfigurasiCache::lupakan();
    }
}

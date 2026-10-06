<?php

namespace App\Policies;

class PelatihanPolicy extends RiwayatPolicy
{
    protected function izinKelola(): string
    {
        return 'riwayat-akademik.kelola';
    }
}

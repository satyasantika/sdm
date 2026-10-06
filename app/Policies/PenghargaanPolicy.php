<?php

namespace App\Policies;

class PenghargaanPolicy extends RiwayatPolicy
{
    protected function izinKelola(): string
    {
        return 'riwayat-akademik.kelola';
    }
}

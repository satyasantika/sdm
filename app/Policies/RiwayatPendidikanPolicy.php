<?php

namespace App\Policies;

class RiwayatPendidikanPolicy extends RiwayatPolicy
{
    protected function izinKelola(): string
    {
        return 'riwayat-akademik.kelola';
    }
}

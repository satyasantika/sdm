<?php

namespace App\Policies;

class RiwayatJabatanFungsionalPolicy extends RiwayatPolicy
{
    protected function izinKelola(): string
    {
        return 'riwayat-akademik.kelola';
    }
}

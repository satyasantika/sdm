<?php

namespace App\Policies;

class SertifikasiPolicy extends RiwayatPolicy
{
    protected function izinKelola(): string
    {
        return 'riwayat-akademik.kelola';
    }
}

<?php

namespace App\Policies;

class RiwayatPangkatPolicy extends RiwayatPolicy
{
    protected function izinKelola(): string
    {
        return 'riwayat-kepegawaian.kelola';
    }
}

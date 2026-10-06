<?php

namespace App\Policies;

class RiwayatKgbPolicy extends RiwayatPolicy
{
    protected function izinKelola(): string
    {
        return 'riwayat-kepegawaian.kelola';
    }
}

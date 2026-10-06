<?php

namespace App\Policies;

class RiwayatJabatanStrukturalPolicy extends RiwayatPolicy
{
    protected function izinKelola(): string
    {
        return 'riwayat-kepegawaian.kelola';
    }
}

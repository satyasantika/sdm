<?php

namespace App\Filament\Admin\Widgets\Concerns;

use App\Enums\Peran;

/** Prodi yang dipakai widget: admin-prodi terkunci prodinya; selain itu mengikuti filter halaman. */
trait MemakaiProdiDasbor
{
    protected function prodiId(): ?string
    {
        $user = auth()->user();

        if ($user && $user->hasRole(Peran::AdminProdi->value) && ! $user->hasAnyRole([Peran::SuperAdmin->value, Peran::AdminKepegawaian->value])) {
            return $user->prodi_id ?? '00000000-0000-0000-0000-000000000000';
        }

        $nilai = $this->pageFilters['prodi_id'] ?? null;

        return filled($nilai) ? (string) $nilai : null;
    }
}

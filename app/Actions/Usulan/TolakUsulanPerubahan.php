<?php

namespace App\Actions\Usulan;

use App\Enums\StatusUsulan;
use App\Models\User;
use App\Models\UsulanPerubahan;
use App\Notifications\UsulanDiputuskan;
use Illuminate\Validation\ValidationException;

class TolakUsulanPerubahan extends KeputusanUsulan
{
    public function handle(UsulanPerubahan $usulan, User $verifikator, ?string $catatan): UsulanPerubahan
    {
        $catatan = trim((string) $catatan);

        if (mb_strlen($catatan) < 10) {
            throw ValidationException::withMessages(['catatan' => 'Catatan penolakan wajib diisi minimal 10 karakter.']);
        }

        $hasil = $this->jalankan($usulan, fn (UsulanPerubahan $terkini) => $this->ubahStatus($terkini, StatusUsulan::Ditolak, $verifikator, $catatan));
        $hasil->pengusul->notify(new UsulanDiputuskan($hasil));

        return $hasil;
    }
}

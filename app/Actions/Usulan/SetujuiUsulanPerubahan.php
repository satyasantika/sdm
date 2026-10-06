<?php

namespace App\Actions\Usulan;

use App\Enums\StatusUsulan;
use App\Exceptions\KonflikDataUsulan;
use App\Models\User;
use App\Models\UsulanPerubahan;
use App\Notifications\UsulanDiputuskan;

class SetujuiUsulanPerubahan extends KeputusanUsulan
{
    public function handle(UsulanPerubahan $usulan, User $verifikator, bool $konfirmasiKonflik = false): UsulanPerubahan
    {
        $hasil = $this->jalankan($usulan, function (UsulanPerubahan $terkini) use ($verifikator, $konfirmasiKonflik): void {
            if (! $konfirmasiKonflik && $terkini->adaKonflik()) {
                throw new KonflikDataUsulan('Data berubah sejak usulan diajukan. Periksa kembali lalu setujui dengan konfirmasi bila tetap sesuai.');
            }

            $snapshot = app(TerapkanUsulanPerubahan::class)->handle($terkini, $verifikator);

            $this->ubahStatus($terkini, StatusUsulan::Disetujui, $verifikator, null, [
                'snapshot_tautan' => $snapshot,
                'diterapkan_at' => now(),
            ]);
        });

        $hasil->pengusul->notify(new UsulanDiputuskan($hasil));

        return $hasil;
    }
}

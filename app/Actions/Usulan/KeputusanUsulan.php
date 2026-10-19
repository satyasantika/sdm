<?php

namespace App\Actions\Usulan;

use App\Enums\StatusUsulan;
use App\Exceptions\TransisiUsulanTidakSah;
use App\Exceptions\UsulanSedangDiproses;
use App\Models\User;
use App\Models\UsulanPerubahan;
use Closure;
use Illuminate\Support\Facades\DB;

/**
 * Dasar aksi verifikasi (BR-06, STANDAR-TEKNIS §1b): tanpa kunci cache; di dalam DB::transaction usulan dimuat ulang
 * dengan lockForUpdate sehingga keputusan bersamaan diserialkan, dan usulan wajib masih berstatus diajukan.
 */
abstract class KeputusanUsulan
{
    /** @param  Closure(UsulanPerubahan): mixed  $isi  dijalankan di dalam transaksi pada usulan yang sudah di-lock */
    protected function jalankan(UsulanPerubahan $usulan, Closure $isi): UsulanPerubahan
    {
        return DB::transaction(function () use ($usulan, $isi): UsulanPerubahan {
            $terkini = UsulanPerubahan::query()->lockForUpdate()->findOrFail($usulan->getKey());

            if ($terkini->status !== StatusUsulan::Diajukan) {
                // Draf: belum pernah diajukan. Selain itu: sudah diputuskan/ditarik pengguna lain.
                throw $terkini->status === StatusUsulan::Draf
                    ? new TransisiUsulanTidakSah("Usulan berstatus {$terkini->status->getLabel()}; hanya usulan Diajukan yang dapat diputuskan.")
                    : new UsulanSedangDiproses('Usulan sudah/sedang diproses pengguna lain.');
            }

            $isi($terkini);

            return $terkini->refresh();
        });
    }

    /** @param  array<string, mixed>  $atribut */
    protected function ubahStatus(UsulanPerubahan $usulan, StatusUsulan $ke, User $oleh, ?string $catatan, array $atribut = []): void
    {
        app(UbahStatusUsulan::class)->handle($usulan, $ke, $oleh, $catatan, $atribut + [
            'diverifikasi_oleh' => $oleh->getKey(),
            'diverifikasi_at' => now(),
            'catatan_verifikator' => $catatan,
        ]);
    }
}

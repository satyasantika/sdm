<?php

namespace App\Actions\Usulan;

use App\Enums\StatusUsulan;
use App\Exceptions\TransisiUsulanTidakSah;
use App\Exceptions\UsulanSedangDiproses;
use App\Models\User;
use App\Models\UsulanPerubahan;
use Closure;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/** Dasar aksi verifikasi: lock Redis per usulan (BR-06), transaksi, muat ulang lockForUpdate, wajib berstatus diajukan. */
abstract class KeputusanUsulan
{
    /** @param  Closure(UsulanPerubahan): mixed  $isi  dijalankan di dalam lock + transaksi pada usulan yang sudah di-lock */
    protected function jalankan(UsulanPerubahan $usulan, Closure $isi): UsulanPerubahan
    {
        $lock = Cache::lock('sdm:lock:usulan:'.$usulan->getKey(), 30);

        if (! $lock->get()) {
            throw new UsulanSedangDiproses('Usulan sedang diproses pengguna lain.');
        }

        try {
            $hasil = DB::transaction(function () use ($usulan, $isi): UsulanPerubahan {
                $terkini = UsulanPerubahan::query()->lockForUpdate()->findOrFail($usulan->getKey());

                if ($terkini->status !== StatusUsulan::Diajukan) {
                    throw new TransisiUsulanTidakSah("Usulan berstatus {$terkini->status->getLabel()}; hanya usulan Diajukan yang dapat diputuskan.");
                }

                $isi($terkini);

                return $terkini->refresh();
            });
        } finally {
            $lock->release();
        }

        return $hasil;
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

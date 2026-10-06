<?php

namespace App\Actions\Usulan;

use App\Enums\JenisUsulan;
use App\Enums\StatusUsulan;
use App\Models\User;
use App\Models\UsulanPerubahan;
use App\Support\RegistriTargetUsulan;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Pengusul memperbaiki isi usulan yang masih draf/dikembalikan sebelum mengirim ulang. */
class PerbaruiUsulanPerubahan
{
    /** @param  array<string, mixed>  $dataBaru */
    public function handle(UsulanPerubahan $usulan, User $pengusul, array $dataBaru, ?string $alasan = null): UsulanPerubahan
    {
        if ($usulan->diajukan_oleh !== $pengusul->getKey()) {
            throw new AuthorizationException('Anda hanya dapat mengubah usulan milik sendiri.');
        }

        if (! in_array($usulan->status, [StatusUsulan::Draf, StatusUsulan::Dikembalikan], true)) {
            throw ValidationException::withMessages(['status' => 'Usulan hanya dapat diubah saat draf atau dikembalikan.']);
        }

        if ($usulan->jenis === JenisUsulan::HapusRiwayat) {
            throw ValidationException::withMessages(['jenis' => 'Usulan hapus tidak memiliki data yang dapat diubah.']);
        }

        $buat = app(BuatUsulanPerubahan::class);
        $tautan = $buat->tautanTarget($dataBaru);
        $baru = $buat->siapkanDataBaru($usulan->target_tabel, $usulan->jenis, RegistriTargetUsulan::saring($usulan->target_tabel, $dataBaru), $usulan->data_lama);
        if ($tautan !== []) {
            $baru['tautan'] = $tautan;
        }

        DB::transaction(fn () => $usulan->update(['data_baru' => $baru, 'alasan' => $alasan ?? $usulan->alasan]));

        return $usulan->refresh();
    }
}

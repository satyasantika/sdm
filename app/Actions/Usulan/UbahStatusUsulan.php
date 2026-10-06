<?php

namespace App\Actions\Usulan;

use App\Enums\StatusUsulan;
use App\Exceptions\TransisiUsulanTidakSah;
use App\Models\RiwayatStatusUsulan;
use App\Models\User;
use App\Models\UsulanPerubahan;

/** Satu-satunya pintu perubahan status usulan: memeriksa transisi, menulis riwayat, melepas kunci_aktif bila final. */
class UbahStatusUsulan
{
    /** @param  array<string, mixed>  $atribut  atribut tambahan yang ikut disimpan bersama status */
    public function handle(UsulanPerubahan $usulan, StatusUsulan $ke, User $oleh, ?string $catatan = null, array $atribut = []): UsulanPerubahan
    {
        $dari = $usulan->status;

        if (! $dari->bolehBerpindahKe($ke)) {
            throw new TransisiUsulanTidakSah("Usulan berstatus {$dari->getLabel()} tidak dapat berpindah ke {$ke->getLabel()}.");
        }

        $usulan->forceFill($atribut + ['status' => $ke]);

        if ($ke->isFinal()) {
            $usulan->kunci_aktif = null;
        }

        $usulan->save();

        RiwayatStatusUsulan::create([
            'usulan_perubahan_id' => $usulan->getKey(),
            'dari_status' => $dari,
            'ke_status' => $ke,
            'oleh_user_id' => $oleh->getKey(),
            'catatan' => $catatan,
        ]);

        return $usulan;
    }
}

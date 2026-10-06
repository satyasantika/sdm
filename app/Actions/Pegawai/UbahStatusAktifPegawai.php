<?php

namespace App\Actions\Pegawai;

use App\Enums\StatusAktifPegawai;
use App\Exceptions\StatusTidakDiizinkan;
use App\Models\Pegawai;
use App\Models\RiwayatStatusPegawai;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class UbahStatusAktifPegawai
{
    public function handle(
        Pegawai $pegawai,
        StatusAktifPegawai $ke,
        CarbonImmutable $tmt,
        ?string $nomorSk,
        ?string $catatan,
        User $oleh,
    ): RiwayatStatusPegawai {
        return DB::transaction(function () use ($pegawai, $ke, $tmt, $nomorSk, $catatan, $oleh): RiwayatStatusPegawai {
            $pegawai = Pegawai::query()->lockForUpdate()->findOrFail($pegawai->getKey());
            $dari = $pegawai->status_aktif;

            if (! $dari->bolehBerpindahKe($ke)) {
                throw new StatusTidakDiizinkan("Status tidak dapat berpindah dari {$dari->getLabel()} ke {$ke->getLabel()}.");
            }

            $pegawai->update(['status_aktif' => $ke]);

            return RiwayatStatusPegawai::create([
                'pegawai_id' => $pegawai->getKey(),
                'dari_status' => $dari,
                'ke_status' => $ke,
                'tmt' => $tmt,
                'nomor_sk' => $nomorSk,
                'catatan' => $catatan,
                'oleh_user_id' => $oleh->getKey(),
            ]);
        });
    }
}

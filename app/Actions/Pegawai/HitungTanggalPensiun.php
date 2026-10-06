<?php

namespace App\Actions\Pegawai;

use App\Enums\JenisPegawai;
use App\Models\JabatanFungsional;
use App\Models\Pegawai;
use App\Support\Konfigurasi;
use Carbon\CarbonImmutable;

/** BR-12: tanggal pensiun = tanggal lahir + BUP (dari konfigurasi), dibulatkan sesuai konfigurasi. */
class HitungTanggalPensiun
{
    public function handle(Pegawai $pegawai): ?CarbonImmutable
    {
        if ($pegawai->tanggal_lahir === null) {
            return null;
        }

        $bup = $this->bup($pegawai);
        if ($bup === null) {
            return null;
        }

        $tanggal = CarbonImmutable::parse($pegawai->tanggal_lahir)->addYears($bup);

        return match (Konfigurasi::get('pembulatan_tmt_pensiun', 'awal_bulan_berikutnya')) {
            'tepat' => $tanggal,
            'akhir_bulan' => $tanggal->endOfMonth()->startOfDay(),
            default => $tanggal->addMonthNoOverflow()->startOfMonth(),
        };
    }

    private function bup(Pegawai $pegawai): ?int
    {
        if ($pegawai->jenis_pegawai === JenisPegawai::Tendik) {
            return $this->angka('bup_tendik');
        }

        $puncak = $pegawai->jabatan_fungsional_id
            && JabatanFungsional::whereKey($pegawai->jabatan_fungsional_id)->value('is_puncak');

        return $this->angka($puncak ? 'bup_profesor' : 'bup_dosen');
    }

    private function angka(string $kunci): ?int
    {
        $nilai = Konfigurasi::get($kunci);

        return $nilai === null ? null : (int) $nilai;
    }
}

<?php

namespace App\Actions\Pengingat;

use App\Enums\JenisPengingat;
use App\Enums\StatusAktifPegawai;
use App\Enums\StatusPengingat;
use App\Models\DokumenPegawai;
use App\Models\Pegawai;
use App\Models\Pengingat;
use App\Models\RiwayatJabatanFungsional;
use App\Models\RiwayatKgb;
use App\Models\RiwayatPangkat;
use App\Models\Sertifikasi;
use App\Models\StudiLanjut;
use App\Support\Konfigurasi;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Menghitung pengingat tenggat satu pegawai dari konfigurasi/master (BR-13 s.d. BR-15, BR-20). Idempoten.
 */
class HitungPengingatPegawai
{
    /** @return Collection<int, Pengingat> */
    public function handle(Pegawai $pegawai): Collection
    {
        if (! in_array($pegawai->status_aktif, [StatusAktifPegawai::Aktif, StatusAktifPegawai::TugasBelajar], true)) {
            return collect();
        }

        $pegawai->loadMissing('statusKepegawaian');
        $hasil = collect();

        foreach ($this->kandidat($pegawai) as [$jenis, $tabel, $id, $tanggal]) {
            $pengingat = $this->simpan($pegawai, $jenis, $tabel, $id, $tanggal);
            if ($pengingat) {
                $hasil->push($pengingat);
            }
        }

        return $hasil;
    }

    /** @return list<array{0: JenisPengingat, 1: string, 2: string, 3: CarbonImmutable}> */
    private function kandidat(Pegawai $pegawai): array
    {
        $daftar = [];
        $status = $pegawai->statusKepegawaian;

        $pangkat = RiwayatPangkat::query()->where('pegawai_id', $pegawai->getKey())->where('is_terkini', true)->first();

        if ($status->berlaku_kenaikan_pangkat && $pangkat) {
            $daftar[] = [JenisPengingat::KenaikanPangkat, 'riwayat_pangkat', (string) $pangkat->getKey(),
                CarbonImmutable::parse($pangkat->tmt)->addMonthsNoOverflow((int) Konfigurasi::get('interval_kp_bulan', 48))];
        }

        if ($status->berlaku_kgb) {
            $kgb = RiwayatKgb::query()->where('pegawai_id', $pegawai->getKey())->where('is_terkini', true)->first();
            $dasar = $kgb ?? $pangkat;

            if ($dasar) {
                $daftar[] = [JenisPengingat::Kgb, $dasar->getTable(), (string) $dasar->getKey(),
                    CarbonImmutable::parse($dasar->tmt)->addMonthsNoOverflow((int) Konfigurasi::get('interval_kgb_bulan', 24))];
            }
        }

        $jabfung = RiwayatJabatanFungsional::query()->where('pegawai_id', $pegawai->getKey())->where('is_terkini', true)->with('jabatanFungsional')->first();
        if ($jabfung && ! $jabfung->jabatanFungsional->is_puncak) {
            $masa = $jabfung->jabatanFungsional->masa_kerja_minimal_bulan;

            if ($masa === null) {
                logger()->info("Pengingat kenaikan jabatan dilewati: syarat belum diisi di master ({$jabfung->jabatanFungsional->nama}).");
            } else {
                $daftar[] = [JenisPengingat::KenaikanJabfung, 'riwayat_jabatan_fungsional', (string) $jabfung->getKey(),
                    CarbonImmutable::parse($jabfung->tmt)->addMonthsNoOverflow((int) $masa)];
            }
        }

        if ($pegawai->tanggal_pensiun) {
            $daftar[] = [JenisPengingat::Pensiun, 'pegawai', (string) $pegawai->getKey(), CarbonImmutable::parse($pegawai->tanggal_pensiun)];
        }

        foreach (DokumenPegawai::query()->where('pegawai_id', $pegawai->getKey())->whereNotNull('tanggal_kedaluwarsa')->get() as $dokumen) {
            $daftar[] = [JenisPengingat::DokumenKedaluwarsa, 'dokumen_pegawai', (string) $dokumen->getKey(), CarbonImmutable::parse($dokumen->tanggal_kedaluwarsa)];
        }

        foreach (Sertifikasi::query()->where('pegawai_id', $pegawai->getKey())->whereNotNull('tanggal_kedaluwarsa')->get() as $sertifikat) {
            $daftar[] = [JenisPengingat::SertifikasiKedaluwarsa, 'sertifikasi', (string) $sertifikat->getKey(), CarbonImmutable::parse($sertifikat->tanggal_kedaluwarsa)];
        }

        foreach (StudiLanjut::query()->where('pegawai_id', $pegawai->getKey())->whereNotNull('tanggal_selesai_rencana')->get() as $studi) {
            if ($studi->status->berlangsung()) {
                $daftar[] = [JenisPengingat::StudiLanjutBerakhir, 'studi_lanjut', (string) $studi->getKey(), CarbonImmutable::parse($studi->tanggal_selesai_rencana)];
            }
        }

        return $daftar;
    }

    private function simpan(Pegawai $pegawai, JenisPengingat $jenis, string $tabel, string $id, CarbonInterface $tanggal): ?Pengingat
    {
        $hariIni = CarbonImmutable::today();
        $tanggal = CarbonImmutable::parse($tanggal)->startOfDay();

        // Tanggal berubah: pengingat lama untuk referensi yang sama ditutup.
        Pengingat::query()
            ->where('pegawai_id', $pegawai->getKey())->where('jenis', $jenis->value)
            ->where('referensi_tabel', $tabel)->where('referensi_id', $id)
            ->whereDate('tanggal_jatuh_tempo', '!=', $tanggal->toDateString())
            ->whereIn('status', StatusPengingat::nilaiBerjalan())
            ->update(['status' => StatusPengingat::Selesai->value]);

        $cakrawala = $hariIni->addDays((int) Konfigurasi::get('cakrawala_pengingat_hari', 180));
        if ($tanggal->greaterThan($cakrawala)) {
            return null;
        }

        $statusBaru = $tanggal->lessThan($hariIni) ? StatusPengingat::LewatTempo : StatusPengingat::Aktif;

        // Kolom date disimpan sebagai datetime oleh cast; cocokkan dengan whereDate agar idempoten.
        $pengingat = Pengingat::query()
            ->where('pegawai_id', $pegawai->getKey())->where('jenis', $jenis->value)
            ->where('referensi_tabel', $tabel)->where('referensi_id', $id)
            ->whereDate('tanggal_jatuh_tempo', $tanggal->toDateString())
            ->first()
            ?? new Pengingat([
                'pegawai_id' => $pegawai->getKey(), 'jenis' => $jenis, 'referensi_tabel' => $tabel,
                'referensi_id' => $id, 'tanggal_jatuh_tempo' => $tanggal->toDateString(),
            ]);

        if (! $pengingat->exists) {
            $pengingat->status = $statusBaru;
        } elseif (in_array($pengingat->status, [StatusPengingat::Aktif, StatusPengingat::LewatTempo], true)) {
            $pengingat->status = $statusBaru;
        }

        $pengingat->save();

        return $pengingat;
    }
}

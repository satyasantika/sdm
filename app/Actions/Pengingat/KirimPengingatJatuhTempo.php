<?php

namespace App\Actions\Pengingat;

use App\Enums\JenisPegawai;
use App\Enums\Peran;
use App\Enums\StatusPengingat;
use App\Models\Pengingat;
use App\Models\User;
use App\Notifications\PengingatJatuhTempo;
use App\Notifications\RingkasanPengingatHarian;
use App\Support\Konfigurasi;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/** BR-19: pengingat dikirim per tahap (default H-90/H-30/H-7), satu tahap hanya sekali. */
class KirimPengingatJatuhTempo
{
    /**
     * @return array{pengingat: int, notifikasi: int}
     */
    public function handle(): array
    {
        $terkirim = $this->tandaiTahap();
        $notifikasi = 0;

        foreach ($terkirim as $pengingat) {
            $user = $pengingat->pegawai->user;

            if ($user) {
                $user->notify(new PengingatJatuhTempo($pengingat, true));
                $notifikasi++;
            }
        }

        if ($terkirim->isEmpty()) {
            return ['pengingat' => 0, 'notifikasi' => 0];
        }

        foreach (User::query()->whereHas('roles', fn ($q) => $q->where('name', Peran::AdminKepegawaian->value))->get() as $admin) {
            $admin->notify(new RingkasanPengingatHarian($terkirim));
            $notifikasi++;
        }

        $perProdi = $terkirim->filter(fn (Pengingat $p) => $p->pegawai->jenis_pegawai === JenisPegawai::Dosen && $p->pegawai->prodi_id !== null)
            ->groupBy(fn (Pengingat $p) => $p->pegawai->prodi_id);

        foreach ($perProdi as $prodiId => $daftar) {
            foreach (User::query()->where('prodi_id', $prodiId)->whereHas('roles', fn ($q) => $q->where('name', Peran::AdminProdi->value))->get() as $adminProdi) {
                $adminProdi->notify(new RingkasanPengingatHarian($daftar->values(), $daftar->first()->pegawai->prodi?->nama));
                $notifikasi++;
            }
        }

        return ['pengingat' => $terkirim->count(), 'notifikasi' => $notifikasi];
    }

    /**
     * Menentukan tahap tiap pengingat dan menandainya terkirim dalam transaksi + lockForUpdate.
     *
     * @return Collection<int, Pengingat>
     */
    private function tandaiTahap(): Collection
    {
        $tahapKonfigurasi = collect((array) Konfigurasi::get('tahap_pengingat_hari', [90, 30, 7]))->map(fn ($t) => (int) $t)->sort()->values();
        $hasil = collect();

        Pengingat::query()
            ->whereIn('status', [StatusPengingat::Aktif->value, StatusPengingat::LewatTempo->value])
            ->with(['pegawai.user', 'pegawai.prodi'])
            ->chunkById(200, function ($kelompok) use ($tahapKonfigurasi, $hasil): void {
                foreach ($kelompok as $pengingat) {
                    $dikirim = DB::transaction(function () use ($pengingat, $tahapKonfigurasi): bool {
                        $terkini = Pengingat::query()->lockForUpdate()->find($pengingat->getKey());
                        $sisa = $terkini->sisaHari();
                        $sudah = array_map('intval', $terkini->tahap_terkirim ?? []);

                        // Tahap layak: nilai konfigurasi ≥ sisa hari; lewat tempo menambah tahap 0.
                        $layak = $tahapKonfigurasi->filter(fn (int $t): bool => $t >= $sisa)->when($sisa < 0, fn ($c) => $c->push(0))->values()->all();
                        $belum = array_values(array_diff($layak, $sudah));

                        if ($belum === []) {
                            return false;
                        }

                        $semuaTahap = array_values(array_unique([...$sudah, ...$layak]));
                        rsort($semuaTahap);

                        $terkini->forceFill([
                            'tahap_terkirim' => $semuaTahap,
                            'terakhir_dikirim_at' => now(),
                        ])->save();

                        return true;
                    });

                    if ($dikirim) {
                        $hasil->push($pengingat->refresh());
                    }
                }
            });

        return $hasil->values();
    }
}

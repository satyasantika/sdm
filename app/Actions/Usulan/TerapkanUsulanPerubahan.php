<?php

namespace App\Actions\Usulan;

use App\Actions\Berkas\GantiTautanBerkas;
use App\Actions\Berkas\SimpanTautanBerkas;
use App\Enums\JenisTautan;
use App\Enums\JenisUsulan;
use App\Models\Pegawai;
use App\Models\TautanBerkas;
use App\Models\User;
use App\Models\UsulanPerubahan;
use App\Support\RegistriTargetUsulan;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

/** Menerapkan data usulan ke target (dipanggil hanya dari SetujuiUsulanPerubahan di dalam lock + transaksi). */
class TerapkanUsulanPerubahan
{
    /**
     * @return array<int, array<string, mixed>> snapshot tautan (bukti + tautan yang diterapkan), BR-33
     */
    public function handle(UsulanPerubahan $usulan, User $verifikator): array
    {
        $pegawai = $usulan->pegawai;
        $tabel = $usulan->target_tabel;
        $data = $usulan->data_baru ?? [];
        $tautan = (array) ($data['tautan'] ?? []);
        unset($data['tautan']);

        $target = $usulan->targetSaatIni();

        if ($usulan->jenis !== JenisUsulan::TambahRiwayat && $target === null) {
            throw ValidationException::withMessages(['target' => 'Data yang dituju sudah tidak ada.']);
        }

        $diterapkan = null;

        if ($usulan->jenis === JenisUsulan::HapusRiwayat) {
            RegistriTargetUsulan::hapus($tabel, $target);
        } else {
            $diterapkan = RegistriTargetUsulan::terapkan($tabel, $pegawai, $data, $verifikator, $usulan->jenis === JenisUsulan::TambahRiwayat ? null : $target);
        }

        $snapshot = $this->snapshotTautan(TautanBerkas::query()->where('pemilik_type', $usulan->getMorphClass())->where('pemilik_id', $usulan->getKey())->get()->all(), 'bukti');

        if ($diterapkan !== null && $tabel !== 'pegawai') {
            foreach ($tautan as $nama => $url) {
                $jenis = JenisTautan::tryFrom((string) $nama) ?? JenisTautan::Lainnya;
                // Pengusul sudah mengonfirmasi berbagi terbatas saat mengajukan; admin memeriksa buktinya saat verifikasi.
                $baru = $this->simpanTautan($diterapkan, $jenis, (string) $url, $verifikator);
                $snapshot = [...$snapshot, ...$this->snapshotTautan([$baru], 'diterapkan')];
            }
        }

        return $snapshot;
    }

    private function simpanTautan(Model $record, JenisTautan $jenis, string $url, User $oleh): TautanBerkas
    {
        $pegawaiId = $record->getAttribute('pegawai_id');
        $ada = method_exists($record, 'tautan') ? $record->tautan($jenis) : null;

        return $ada
            ? app(GantiTautanBerkas::class)->handle($ada, $url, $oleh, true)
            : app(SimpanTautanBerkas::class)->handle($record, $jenis, $url, null, $pegawaiId ? Pegawai::find($pegawaiId) : null, $oleh, true);
    }

    /**
     * @param  array<int, TautanBerkas>  $tautan
     * @return array<int, array<string, mixed>>
     */
    private function snapshotTautan(array $tautan, string $peran): array
    {
        return array_map(fn (TautanBerkas $m): array => [
            'peran' => $peran,
            'url' => $m->url,
            'jenis' => $m->jenis->value,
            'drive_file_id' => $m->drive_file_id,
            'status_cek' => $m->status_cek->value,
            'dicek_pada' => $m->dicek_pada?->toIso8601String(),
        ], $tautan);
    }
}

<?php

namespace App\Actions\Riwayat;

use App\Actions\Pegawai\UbahStatusAktifPegawai;
use App\Enums\JenisStudiLanjut;
use App\Enums\StatusAktifPegawai;
use App\Enums\StatusStudiLanjut;
use App\Exceptions\StatusTidakDiizinkan;
use App\Models\Pegawai;
use App\Models\StudiLanjut;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SimpanStudiLanjut
{
    /**
     * @param  array<string, mixed>  $data
     * @param  bool  $sinkronStatus  selaraskan status keaktifan pegawai (hanya bila user boleh mengubah pegawai)
     */
    public function handle(Pegawai $pegawai, array $data, User $oleh, ?StudiLanjut $record = null, bool $sinkronStatus = true): StudiLanjut
    {
        $jenis = $this->enum(JenisStudiLanjut::class, $data['jenis']);
        $status = $this->enum(StatusStudiLanjut::class, $data['status'] ?? 'berjalan');
        $mulai = CarbonImmutable::parse($data['tanggal_mulai']);

        foreach (['tanggal_selesai_rencana', 'tanggal_selesai_aktual'] as $kolom) {
            if (filled($data[$kolom] ?? null) && CarbonImmutable::parse($data[$kolom])->lessThan($mulai)) {
                throw ValidationException::withMessages([$kolom => 'Tanggal selesai tidak boleh sebelum tanggal mulai.']);
            }
        }

        return DB::transaction(function () use ($pegawai, $data, $oleh, $record, $sinkronStatus, $jenis, $status): StudiLanjut {
            $pegawai = Pegawai::query()->lockForUpdate()->findOrFail($pegawai->getKey());

            $atribut = collect($data)->only([
                'jenjang_pendidikan_id', 'jenis', 'nama_pt', 'negara', 'nama_prodi', 'sumber_biaya', 'nomor_sk',
                'tanggal_mulai', 'tanggal_selesai_rencana', 'tanggal_selesai_aktual', 'status',
            ])->map(fn ($v) => $v === '' ? null : $v)->all();
            $atribut['negara'] ??= 'Indonesia';

            $record
                ? $record->update($atribut)
                : $record = StudiLanjut::create($atribut + ['pegawai_id' => $pegawai->getKey()]);

            if ($sinkronStatus && $jenis === JenisStudiLanjut::TugasBelajar && $oleh->can('pegawai.ubah')) {
                $this->selaraskanStatusPegawai($pegawai, $record, $status, $oleh);
            }

            return $record->refresh();
        });
    }

    private function selaraskanStatusPegawai(Pegawai $pegawai, StudiLanjut $studi, StatusStudiLanjut $status, User $oleh): void
    {
        $ubah = app(UbahStatusAktifPegawai::class);

        try {
            if ($status->berlangsung() && $pegawai->status_aktif === StatusAktifPegawai::Aktif) {
                $ubah->handle($pegawai, StatusAktifPegawai::TugasBelajar, CarbonImmutable::parse($studi->tanggal_mulai), $studi->nomor_sk, 'Tugas belajar: '.$studi->nama_pt, $oleh);
            } elseif (! $status->berlangsung() && $pegawai->status_aktif === StatusAktifPegawai::TugasBelajar) {
                $tmt = $studi->tanggal_selesai_aktual ? CarbonImmutable::parse($studi->tanggal_selesai_aktual) : CarbonImmutable::today();
                $ubah->handle($pegawai, StatusAktifPegawai::Aktif, $tmt, $studi->nomor_sk, 'Selesai tugas belajar: '.$studi->nama_pt, $oleh);
            }
        } catch (StatusTidakDiizinkan) {
            // transisi tidak diizinkan oleh diagram status; data studi tetap tersimpan.
        }
    }

    /**
     * @template T of \BackedEnum
     *
     * @param  class-string<T>  $kelas
     * @return T
     */
    private function enum(string $kelas, mixed $nilai): \BackedEnum
    {
        return $nilai instanceof $kelas ? $nilai : $kelas::from($nilai);
    }
}

<?php

namespace App\Actions\Riwayat;

use App\Models\Pegawai;
use App\Models\RiwayatJabatanStruktural;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SimpanRiwayatJabatanStruktural
{
    /** @param  array<string, mixed>  $data */
    public function handle(Pegawai $pegawai, array $data, ?RiwayatJabatanStruktural $record = null): RiwayatJabatanStruktural
    {
        return DB::transaction(function () use ($pegawai, $data, $record): RiwayatJabatanStruktural {
            $pegawai = Pegawai::query()->lockForUpdate()->findOrFail($pegawai->getKey());
            $mulai = CarbonImmutable::parse($data['tmt_mulai']);
            $selesai = filled($data['tmt_selesai'] ?? null) ? CarbonImmutable::parse($data['tmt_selesai']) : null;

            if ($selesai && $selesai->lessThan($mulai)) {
                throw ValidationException::withMessages(['tmt_selesai' => 'Tanggal selesai tidak boleh sebelum tanggal mulai.']);
            }

            if ($this->tumpangTindih($pegawai, $data, $mulai, $selesai, $record)) {
                throw ValidationException::withMessages([
                    'tmt_mulai' => 'Pegawai sudah memiliki periode untuk jabatan dan unit yang sama yang tumpang tindih.',
                ]);
            }

            $atribut = [
                'jenis_jabatan_struktural_id' => $data['jenis_jabatan_struktural_id'],
                'unit_kerja_id' => filled($data['unit_kerja_id'] ?? null) ? $data['unit_kerja_id'] : null,
                'tmt_mulai' => $mulai,
                'tmt_selesai' => $selesai,
                'nomor_sk' => $data['nomor_sk'],
                'tanggal_sk' => $data['tanggal_sk'] ?? null,
            ];

            $record
                ? $record->update($atribut)
                : $record = RiwayatJabatanStruktural::create($atribut + ['pegawai_id' => $pegawai->getKey()]);

            return $record->refresh();
        });
    }

    /**
     * Pemegang lain jabatan & unit yang sama pada periode itu (peringatan, bukan penolakan).
     *
     * @return list<string> nama pemegang lain
     */
    public function pemegangLain(RiwayatJabatanStruktural $riwayat): array
    {
        return $this->kueriTumpangTindih(
            $riwayat->jenis_jabatan_struktural_id,
            $riwayat->unit_kerja_id,
            CarbonImmutable::parse($riwayat->tmt_mulai),
            $riwayat->tmt_selesai ? CarbonImmutable::parse($riwayat->tmt_selesai) : null,
        )
            ->where('pegawai_id', '!=', $riwayat->pegawai_id)
            ->with('pegawai')
            ->get()
            ->map(fn (RiwayatJabatanStruktural $r): string => $r->pegawai->nama_bergelar)
            ->unique()->values()->all();
    }

    /** @param  array<string, mixed>  $data */
    private function tumpangTindih(Pegawai $pegawai, array $data, CarbonImmutable $mulai, ?CarbonImmutable $selesai, ?RiwayatJabatanStruktural $sendiri): bool
    {
        return $this->kueriTumpangTindih(
            $data['jenis_jabatan_struktural_id'],
            filled($data['unit_kerja_id'] ?? null) ? $data['unit_kerja_id'] : null,
            $mulai,
            $selesai,
        )
            ->where('pegawai_id', $pegawai->getKey())
            ->when($sendiri, fn ($q) => $q->whereKeyNot($sendiri->getKey()))
            ->exists();
    }

    /** @return Builder<RiwayatJabatanStruktural> */
    private function kueriTumpangTindih(string $jabatanId, ?string $unitId, CarbonImmutable $mulai, ?CarbonImmutable $selesai)
    {
        return RiwayatJabatanStruktural::query()
            ->where('jenis_jabatan_struktural_id', $jabatanId)
            ->where('unit_kerja_id', $unitId)
            ->where(fn ($q) => $q->whereNull('tmt_selesai')->orWhereDate('tmt_selesai', '>=', $mulai->toDateString()))
            ->when($selesai, fn ($q) => $q->whereDate('tmt_mulai', '<=', $selesai->toDateString()));
    }
}

<?php

namespace App\Actions\Laporan;

use App\Enums\JenisGolongan;
use App\Enums\StatusAktifPegawai;
use App\Models\Pegawai;
use App\Models\RiwayatJabatanStruktural;
use App\Models\RiwayatPangkat;
use App\Models\User;
use App\Support\CakupanProdi;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/** Menyusun data laporan kepegawaian (DUK, pejabat, pensiun). Tanpa data sensitif (BR-17). */
class SusunLaporanKepegawaian
{
    /**
     * DUK (PNS saja, bukan CPNS): urut golongan (urutan desc), TMT golongan (asc), lalu nama.
     * Kriteria urutan DUK perlu verifikasi dengan ketentuan BKN.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function duk(?User $user = null): Collection
    {
        $query = Pegawai::query()
            ->whereIn('status_aktif', [StatusAktifPegawai::Aktif->value, StatusAktifPegawai::TugasBelajar->value])
            ->whereHas('statusKepegawaian', fn (Builder $q) => $q->where('jenis_golongan', JenisGolongan::Pns->value)->where('berlaku_kenaikan_pangkat', true))
            ->with(['golongan', 'jabatanFungsional', 'pendidikanTertinggi.jenjangPendidikan']);

        /** @var Builder<Pegawai> $query */
        $query = CakupanProdi::batasi($query, $user, null); // @phpstan-ignore argument.type

        $pangkat = RiwayatPangkat::query()->where('is_terkini', true)->pluck('tmt', 'pegawai_id');

        /** @var Collection<int, array<string, mixed>> $hasil */
        $hasil = $query->get()
            ->sortBy([
                fn (Pegawai $a, Pegawai $b): int => ((int) $b->golongan?->urutan) <=> ((int) $a->golongan?->urutan),
                fn (Pegawai $a, Pegawai $b): int => strcmp((string) ($pangkat[$a->id] ?? '9999'), (string) ($pangkat[$b->id] ?? '9999')),
                fn (Pegawai $a, Pegawai $b): int => strcmp($a->nama, $b->nama),
            ])
            ->values()
            ->map(function (Pegawai $p) use ($pangkat): array {
                $tmt = isset($pangkat[$p->id]) ? CarbonImmutable::parse($pangkat[$p->id]) : null;
                $mulai = $p->tmt_cpns ?? $p->tmt_masuk;
                $mulai = $mulai ? CarbonImmutable::parse($mulai) : null;

                return [
                    'nama' => $p->nama_bergelar,
                    'golongan' => $p->golongan->label ?? '-',
                    'tmt_golongan' => $tmt?->translatedFormat('d F Y') ?? '-',
                    'jabatan' => $p->jabatanFungsional->nama ?? '-',
                    'masa_kerja' => $mulai && $mulai->lte(now()) ? (int) $mulai->diffInYears(now()).' th' : '-',
                    'pendidikan' => $p->pendidikanTertinggi?->jenjangPendidikan->nama ?? '-',
                    'usia' => $p->tanggal_lahir ? (int) $p->tanggal_lahir->diffInYears(now()).' th' : '-',
                ];
            });

        return $hasil;
    }

    /** @return Collection<int, array<string, mixed>> */
    public function pejabat(?User $user = null): Collection
    {
        $query = RiwayatJabatanStruktural::query()->aktif()->with(['pegawai', 'jenisJabatanStruktural', 'unitKerja']);

        /** @var Collection<int, RiwayatJabatanStruktural> $daftar */
        $daftar = CakupanProdi::batasi($query, $user)->get(); // @phpstan-ignore argument.type

        /** @var Collection<int, array<string, mixed>> $hasil */
        $hasil = $daftar
            ->sortBy(fn (RiwayatJabatanStruktural $r): int => (int) $r->jenisJabatanStruktural->urutan)
            ->values()
            ->map(fn (RiwayatJabatanStruktural $r): array => [
                'jabatan' => $r->jenisJabatanStruktural->nama,
                'nama' => $r->pegawai->nama_bergelar,
                'unit' => $r->unitKerja->nama ?? '-',
                'periode' => $r->periode,
            ]);

        return $hasil;
    }

    /** @return Collection<int, array<string, mixed>> pensiun ≤ 5 tahun, urut tanggal pensiun */
    public function pensiun(?User $user = null): Collection
    {
        $query = Pegawai::query()
            ->whereIn('status_aktif', [StatusAktifPegawai::Aktif->value, StatusAktifPegawai::TugasBelajar->value])
            ->whereBetween('tanggal_pensiun', [now()->toDateString(), now()->addYears(5)->toDateString()])
            ->with(['prodi', 'unitKerja', 'jabatanFungsional'])
            ->orderBy('tanggal_pensiun');

        /** @var Builder<Pegawai> $query */
        $query = CakupanProdi::batasi($query, $user, null); // @phpstan-ignore argument.type

        /** @var Collection<int, array<string, mixed>> $hasil */
        $hasil = $query->get()->map(fn (Pegawai $p): array => [
            'tahun' => (int) $p->tanggal_pensiun->year,
            'nama' => $p->nama_bergelar,
            'jenis' => $p->jenis_pegawai->getLabel(),
            'unit' => $p->prodi->nama ?? $p->unitKerja->nama ?? '-',
            'jabatan' => $p->jabatanFungsional->nama ?? '-',
            'tanggal_pensiun' => $p->tanggal_pensiun->translatedFormat('d F Y'),
        ]);

        return $hasil;
    }
}

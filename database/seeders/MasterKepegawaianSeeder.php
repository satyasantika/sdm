<?php

namespace Database\Seeders;

use App\Enums\JenisGolongan;
use App\Enums\KelompokStatus;
use App\Models\Golongan;
use App\Models\StatusKepegawaian;
use Illuminate\Database\Seeder;

class MasterKepegawaianSeeder extends Seeder
{
    private const PANGKAT_PNS = [
        'I/a' => 'Juru Muda', 'I/b' => 'Juru Muda Tingkat I', 'I/c' => 'Juru', 'I/d' => 'Juru Tingkat I',
        'II/a' => 'Pengatur Muda', 'II/b' => 'Pengatur Muda Tingkat I', 'II/c' => 'Pengatur', 'II/d' => 'Pengatur Tingkat I',
        'III/a' => 'Penata Muda', 'III/b' => 'Penata Muda Tingkat I', 'III/c' => 'Penata', 'III/d' => 'Penata Tingkat I',
        'IV/a' => 'Pembina', 'IV/b' => 'Pembina Tingkat I', 'IV/c' => 'Pembina Utama Muda',
        'IV/d' => 'Pembina Utama Madya', 'IV/e' => 'Pembina Utama',
    ];

    private const ROMAWI = [
        'I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII', 'XIII', 'XIV', 'XV', 'XVI', 'XVII',
    ];

    public function run(): void
    {
        // pppk berlaku_kgb perlu verifikasi.
        $status = [
            ['pns', 'PNS', KelompokStatus::Asn, JenisGolongan::Pns, true, true, true],
            ['cpns', 'CPNS', KelompokStatus::Asn, JenisGolongan::Pns, false, false, true],
            ['pppk', 'PPPK', KelompokStatus::Asn, JenisGolongan::Pppk, false, true, true],
            ['non-asn-tetap-blu', 'Non-ASN Tetap (BLU)', KelompokStatus::NonAsn, null, false, false, true],
            ['non-asn-kontrak', 'Non-ASN Kontrak', KelompokStatus::NonAsn, null, false, false, false],
        ];

        foreach ($status as $urutan => [$kode, $nama, $kelompok, $jenis, $kp, $kgb, $tetap]) {
            StatusKepegawaian::updateOrCreate(['kode' => $kode], [
                'nama' => $nama,
                'kelompok' => $kelompok,
                'jenis_golongan' => $jenis,
                'berlaku_kenaikan_pangkat' => $kp,
                'berlaku_kgb' => $kgb,
                'dihitung_dosen_tetap' => $tetap,
                'urutan' => $urutan + 1,
                'is_aktif' => true,
            ]);
        }

        $urutan = 0;
        foreach (self::PANGKAT_PNS as $kode => $pangkat) {
            Golongan::updateOrCreate(
                ['jenis' => JenisGolongan::Pns, 'kode' => $kode],
                ['pangkat' => $pangkat, 'urutan' => ++$urutan, 'is_aktif' => true],
            );
        }

        foreach (self::ROMAWI as $i => $kode) {
            Golongan::updateOrCreate(
                ['jenis' => JenisGolongan::Pppk, 'kode' => $kode],
                ['pangkat' => null, 'urutan' => $i + 1, 'is_aktif' => true],
            );
        }
    }
}

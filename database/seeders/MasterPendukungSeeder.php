<?php

namespace Database\Seeders;

use App\Models\JenisDokumen;
use App\Models\JenisSertifikasi;
use App\Models\JenjangPendidikan;
use App\Models\Semester;
use Illuminate\Database\Seeder;

class MasterPendukungSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['SD' => 'SD', 'SMP' => 'SMP', 'SMA' => 'SMA/SMK/MA', 'D3' => 'Diploma III', 'D4' => 'Diploma IV',
            'S1' => 'Sarjana (S1)', 'PROFESI' => 'Profesi', 'S2' => 'Magister (S2)', 'SP' => 'Spesialis', 'S3' => 'Doktor (S3)'] as $kode => $nama) {
            static $urutan = 0;
            JenjangPendidikan::updateOrCreate(['kode' => $kode], ['nama' => $nama, 'urutan' => ++$urutan]);
        }

        $sertifikasi = [
            ['serdos', 'Sertifikat Pendidik Dosen', true, false],
            ['kompetensi', 'Sertifikat Kompetensi', false, true],
            ['profesi', 'Sertifikat Profesi', false, false],
            ['lainnya', 'Sertifikat Lainnya', false, false],
        ];
        foreach ($sertifikasi as [$kode, $nama, $serdos, $berlaku]) {
            JenisSertifikasi::updateOrCreate(['kode' => $kode], ['nama' => $nama, 'is_serdos' => $serdos, 'punya_masa_berlaku' => $berlaku]);
        }

        $dokumen = [
            ['ktp', 'KTP', false, true, 'semua'],
            ['npwp', 'NPWP', false, true, 'semua'],
            ['kk', 'Kartu Keluarga', false, true, 'semua'],
            ['karpeg', 'Kartu Pegawai (Karpeg)', false, false, 'asn'],
            ['karis-karsu', 'Karis/Karsu', false, false, 'asn'],
            ['taspen', 'Kartu Taspen', false, false, 'asn'],
            ['bpjs-kesehatan', 'BPJS Kesehatan', true, false, 'semua'],
            ['bpjs-ketenagakerjaan', 'BPJS Ketenagakerjaan', true, false, 'semua'],
            ['paspor', 'Paspor', true, false, null],
            ['sk-cpns', 'SK CPNS', false, false, 'asn'],
            ['sk-pns', 'SK PNS', false, false, 'asn'],
            ['sk-pppk', 'SK PPPK', false, false, 'asn'],
            ['buku-rekening', 'Buku Rekening', false, true, 'semua'],
        ];
        foreach ($dokumen as [$kode, $nama, $berlaku, $identitas, $wajib]) {
            JenisDokumen::updateOrCreate(['kode' => $kode], [
                'nama' => $nama, 'punya_masa_berlaku' => $berlaku, 'is_identitas' => $identitas, 'wajib_untuk' => $wajib,
            ]);
        }

        foreach (['20241', '20242', '20251', '20252', '20261'] as $kode) {
            $tahun = (int) substr($kode, 0, 4);
            Semester::updateOrCreate(['kode' => $kode], [
                'tahun_akademik' => $tahun.'/'.($tahun + 1),
                'jenis' => substr($kode, 4) === '1' ? 'ganjil' : 'genap',
            ]);
        }
        Semester::firstWhere('kode', '20261')?->aktifkan();
    }
}

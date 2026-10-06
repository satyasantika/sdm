<?php

namespace Database\Seeders;

use App\Enums\KelompokJabatan;
use App\Models\JabatanFungsional;
use App\Models\JenisJabatanStruktural;
use Illuminate\Database\Seeder;

class MasterJabatanSeeder extends Seeder
{
    public function run(): void
    {
        // Syarat kenaikan sengaja NULL: diisi admin setelah verifikasi aturan terbaru.
        $dasarHukum = 'Perlu verifikasi: Permenpan RB 1/2023 dan aturan turunan Kemendiktisaintek';

        $dosen = [
            ['tenaga-pengajar', 'Tenaga Pengajar', 0, false],
            ['asisten-ahli', 'Asisten Ahli', 1, false],
            ['lektor', 'Lektor', 2, false],
            ['lektor-kepala', 'Lektor Kepala', 3, false],
            ['profesor', 'Profesor', 4, true],
        ];

        foreach ($dosen as [$kode, $nama, $urutan, $puncak]) {
            JabatanFungsional::updateOrCreate(['kode' => $kode], [
                'nama' => $nama,
                'kelompok' => KelompokJabatan::Dosen,
                'urutan' => $urutan,
                'is_puncak' => $puncak,
                'dasar_hukum' => $dasarHukum,
                'is_aktif' => true,
            ]);
        }

        // Nomenklatur perlu verifikasi terhadap OTK Unsil.
        $struktural = [
            ['dekan', 'Dekan', 'struktural'],
            ['wakil-dekan-akademik', 'Wakil Dekan Bidang Akademik', 'struktural'],
            ['wakil-dekan-umum-keuangan', 'Wakil Dekan Bidang Umum dan Keuangan', 'struktural'],
            ['wakil-dekan-kemahasiswaan', 'Wakil Dekan Bidang Kemahasiswaan', 'struktural'],
            ['ketua-jurusan', 'Ketua Jurusan', 'tugas_tambahan'],
            ['sekretaris-jurusan', 'Sekretaris Jurusan', 'tugas_tambahan'],
            ['koordinator-prodi', 'Koordinator Program Studi', 'tugas_tambahan'],
            ['kepala-laboratorium', 'Kepala Laboratorium', 'tugas_tambahan'],
            ['kepala-subbagian', 'Kepala Subbagian', 'struktural'],
        ];

        foreach ($struktural as $urutan => [$kode, $nama, $kategori]) {
            JenisJabatanStruktural::updateOrCreate(['kode' => $kode], [
                'nama' => $nama,
                'kategori' => $kategori,
                'urutan' => $urutan + 1,
                'is_aktif' => true,
            ]);
        }
    }
}

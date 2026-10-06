<?php

namespace Database\Seeders;

use App\Models\Prodi;
use App\Models\UnitKerja;
use Illuminate\Database\Seeder;

class MasterProdiSeeder extends Seeder
{
    /**
     * Daftar resmi prodi & kode PDDIKTI perlu verifikasi; data di bawah hanya contoh awal.
     */
    public function run(): void
    {
        $prodi = [
            'PMAT' => ['Pendidikan Matematika', 'S1'],
            'PBIO' => ['Pendidikan Biologi', 'S1'],
            'PBI' => ['Pendidikan Bahasa Inggris', 'S1'],
            'PJKR' => ['Pendidikan Jasmani, Kesehatan dan Rekreasi', 'S1'],
            'MPMAT' => ['Magister Pendidikan Matematika', 'S2'],
        ];

        $model = [];
        foreach ($prodi as $kode => [$nama, $jenjang]) {
            $model[$kode] = Prodi::updateOrCreate(['kode' => $kode], ['nama' => $nama, 'jenjang' => $jenjang, 'is_aktif' => true]);
        }

        $fkip = UnitKerja::updateOrCreate(['kode' => 'FKIP'], ['nama' => 'Fakultas Keguruan dan Ilmu Pendidikan', 'jenis' => 'fakultas', 'is_aktif' => true]);

        $subbagian = [
            'SB-UMUM' => 'Subbagian Umum dan Kepegawaian',
            'SB-AKAD' => 'Subbagian Akademik dan Kemahasiswaan',
            'SB-KEU' => 'Subbagian Keuangan',
        ];
        foreach ($subbagian as $kode => $nama) {
            UnitKerja::updateOrCreate(['kode' => $kode], ['nama' => $nama, 'jenis' => 'subbagian', 'induk_id' => $fkip->id, 'is_aktif' => true]);
        }

        foreach (['PMAT', 'PBIO'] as $kode) {
            UnitKerja::updateOrCreate(['kode' => 'LAB-'.$kode], [
                'nama' => 'Laboratorium '.$model[$kode]->nama,
                'jenis' => 'laboratorium',
                'induk_id' => $fkip->id,
                'prodi_id' => $model[$kode]->id,
                'is_aktif' => true,
            ]);
        }
    }
}

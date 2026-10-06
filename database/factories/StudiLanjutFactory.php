<?php

namespace Database\Factories;

use App\Models\JenjangPendidikan;
use App\Models\Pegawai;
use App\Models\StudiLanjut;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StudiLanjut>
 */
class StudiLanjutFactory extends Factory
{
    public function definition(): array
    {
        return [
            'pegawai_id' => Pegawai::factory(),
            'jenjang_pendidikan_id' => JenjangPendidikan::factory(),
            'jenis' => 'tugas_belajar',
            'nama_pt' => 'Universitas '.fake()->city(),
            'tanggal_mulai' => '2025-09-01',
            'status' => 'berjalan',
        ];
    }
}

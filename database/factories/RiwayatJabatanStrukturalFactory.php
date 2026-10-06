<?php

namespace Database\Factories;

use App\Models\JenisJabatanStruktural;
use App\Models\Pegawai;
use App\Models\RiwayatJabatanStruktural;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RiwayatJabatanStruktural>
 */
class RiwayatJabatanStrukturalFactory extends Factory
{
    public function definition(): array
    {
        return [
            'pegawai_id' => Pegawai::factory(),
            'jenis_jabatan_struktural_id' => JenisJabatanStruktural::factory(),
            'tmt_mulai' => fake()->dateTimeBetween('-5 years', '-1 year')->format('Y-m-d'),
            'tmt_selesai' => null,
            'nomor_sk' => fake()->numerify('SK/####/2022'),
        ];
    }
}

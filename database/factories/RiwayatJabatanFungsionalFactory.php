<?php

namespace Database\Factories;

use App\Models\JabatanFungsional;
use App\Models\Pegawai;
use App\Models\RiwayatJabatanFungsional;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RiwayatJabatanFungsional>
 */
class RiwayatJabatanFungsionalFactory extends Factory
{
    public function definition(): array
    {
        return [
            'pegawai_id' => Pegawai::factory(),
            'jabatan_fungsional_id' => JabatanFungsional::factory(),
            'tmt' => fake()->dateTimeBetween('-10 years', '-1 year')->format('Y-m-d'),
            'nomor_sk' => fake()->numerify('SK/####/2020'),
        ];
    }
}

<?php

namespace Database\Factories;

use App\Models\Pegawai;
use App\Models\RiwayatKgb;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RiwayatKgb>
 */
class RiwayatKgbFactory extends Factory
{
    public function definition(): array
    {
        return [
            'pegawai_id' => Pegawai::factory(),
            'tmt' => fake()->dateTimeBetween('-10 years', '-1 year')->format('Y-m-d'),
            'nomor_sk' => fake()->numerify('KGB/####/2020'),
            'gaji_pokok' => 4567800.50,
        ];
    }
}

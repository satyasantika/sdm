<?php

namespace Database\Factories;

use App\Models\Golongan;
use App\Models\Pegawai;
use App\Models\RiwayatPangkat;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RiwayatPangkat>
 */
class RiwayatPangkatFactory extends Factory
{
    public function definition(): array
    {
        return [
            'pegawai_id' => Pegawai::factory(),
            'golongan_id' => Golongan::factory(),
            'tmt' => fake()->dateTimeBetween('-10 years', '-1 year')->format('Y-m-d'),
            'nomor_sk' => fake()->numerify('SK/####/2020'),
            'jenis_kenaikan' => 'reguler',
        ];
    }
}

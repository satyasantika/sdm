<?php

namespace Database\Factories;

use App\Models\Keluarga;
use App\Models\Pegawai;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Keluarga>
 */
class KeluargaFactory extends Factory
{
    public function definition(): array
    {
        return [
            'pegawai_id' => Pegawai::factory(),
            'hubungan' => 'anak',
            'nama' => fake()->name(),
            'nik' => fake()->numerify('################'),
        ];
    }
}

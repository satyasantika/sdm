<?php

namespace Database\Factories;

use App\Models\UnitKerja;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UnitKerja>
 */
class UnitKerjaFactory extends Factory
{
    public function definition(): array
    {
        return [
            'kode' => strtoupper(fake()->unique()->bothify('UK-####')),
            'nama' => 'Unit '.fake()->unique()->words(2, true),
            'jenis' => fake()->randomElement(['fakultas', 'jurusan', 'prodi', 'laboratorium', 'subbagian', 'unit_lain']),
            'induk_id' => null,
            'prodi_id' => null,
            'kode_eksternal' => null,
            'is_aktif' => true,
        ];
    }
}

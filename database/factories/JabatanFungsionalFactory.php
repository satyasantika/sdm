<?php

namespace Database\Factories;

use App\Models\JabatanFungsional;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<JabatanFungsional>
 */
class JabatanFungsionalFactory extends Factory
{
    public function definition(): array
    {
        return [
            'kode' => fake()->unique()->slug(2),
            'nama' => fake()->words(2, true),
            'kelompok' => 'dosen',
            'rumpun' => null,
            'urutan' => fake()->unique()->numberBetween(10, 999),
            'is_puncak' => false,
            'is_aktif' => true,
        ];
    }
}

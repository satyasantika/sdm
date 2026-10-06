<?php

namespace Database\Factories;

use App\Models\JenjangPendidikan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<JenjangPendidikan>
 */
class JenjangPendidikanFactory extends Factory
{
    public function definition(): array
    {
        return [
            'kode' => strtoupper(fake()->unique()->lexify('???')),
            'nama' => fake()->words(2, true),
            'urutan' => fake()->unique()->numberBetween(20, 999),
        ];
    }
}

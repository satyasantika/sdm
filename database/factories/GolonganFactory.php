<?php

namespace Database\Factories;

use App\Models\Golongan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Golongan>
 */
class GolonganFactory extends Factory
{
    public function definition(): array
    {
        return [
            'jenis' => 'pns',
            'kode' => fake()->unique()->bothify('?/?'),
            'pangkat' => fake()->words(2, true),
            'urutan' => fake()->unique()->numberBetween(100, 999),
            'is_aktif' => true,
        ];
    }
}

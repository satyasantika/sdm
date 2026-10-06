<?php

namespace Database\Factories;

use App\Models\Prodi;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Prodi>
 */
class ProdiFactory extends Factory
{
    public function definition(): array
    {
        return [
            'kode' => strtoupper(fake()->unique()->lexify('????')),
            'kode_pddikti' => null,
            'nama' => 'Pendidikan '.fake()->unique()->words(2, true),
            'jenjang' => fake()->randomElement(['S1', 'S2', 'S3', 'PPG', 'D3']),
            'kode_eksternal' => null,
            'is_aktif' => true,
        ];
    }
}

<?php

namespace Database\Factories;

use App\Models\JenisSertifikasi;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<JenisSertifikasi>
 */
class JenisSertifikasiFactory extends Factory
{
    public function definition(): array
    {
        return [
            'kode' => fake()->unique()->slug(2),
            'nama' => fake()->words(3, true),
            'is_serdos' => false,
            'punya_masa_berlaku' => false,
        ];
    }
}

<?php

namespace Database\Factories;

use App\Models\JenisDokumen;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<JenisDokumen>
 */
class JenisDokumenFactory extends Factory
{
    public function definition(): array
    {
        return [
            'kode' => fake()->unique()->slug(2),
            'nama' => fake()->words(3, true),
            'punya_masa_berlaku' => false,
            'is_identitas' => false,
            'wajib_untuk' => null,
        ];
    }
}

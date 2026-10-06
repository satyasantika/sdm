<?php

namespace Database\Factories;

use App\Models\JenisJabatanStruktural;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<JenisJabatanStruktural>
 */
class JenisJabatanStrukturalFactory extends Factory
{
    public function definition(): array
    {
        return [
            'kode' => fake()->unique()->slug(2),
            'nama' => fake()->words(3, true),
            'kategori' => 'struktural',
            'urutan' => 0,
            'is_aktif' => true,
        ];
    }
}

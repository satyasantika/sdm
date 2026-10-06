<?php

namespace Database\Factories;

use App\Models\Pegawai;
use App\Models\Penghargaan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Penghargaan>
 */
class PenghargaanFactory extends Factory
{
    public function definition(): array
    {
        return [
            'pegawai_id' => Pegawai::factory(),
            'kategori' => 'penghargaan',
            'nama' => 'Penghargaan '.fake()->words(2, true),
            'tingkat' => 'universitas',
        ];
    }
}

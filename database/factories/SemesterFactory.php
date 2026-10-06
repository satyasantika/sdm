<?php

namespace Database\Factories;

use App\Models\Semester;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Semester>
 */
class SemesterFactory extends Factory
{
    public function definition(): array
    {
        return [
            'kode' => '20'.fake()->unique()->numberBetween(30, 99).fake()->randomElement(['1', '2']),
            'tahun_akademik' => '2030/2031',
            'jenis' => 'ganjil',
            'tanggal_mulai' => null,
            'tanggal_selesai' => null,
            'is_aktif' => false,
        ];
    }
}

<?php

namespace Database\Factories;

use App\Models\StatusKepegawaian;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StatusKepegawaian>
 */
class StatusKepegawaianFactory extends Factory
{
    public function definition(): array
    {
        return [
            'kode' => fake()->unique()->slug(2),
            'nama' => fake()->words(2, true),
            'kelompok' => 'asn',
            'jenis_golongan' => 'pns',
            'berlaku_kenaikan_pangkat' => true,
            'berlaku_kgb' => true,
            'dihitung_dosen_tetap' => true,
            'urutan' => 0,
            'is_aktif' => true,
        ];
    }
}

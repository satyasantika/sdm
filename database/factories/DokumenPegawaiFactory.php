<?php

namespace Database\Factories;

use App\Models\DokumenPegawai;
use App\Models\JenisDokumen;
use App\Models\Pegawai;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DokumenPegawai>
 */
class DokumenPegawaiFactory extends Factory
{
    public function definition(): array
    {
        return [
            'pegawai_id' => Pegawai::factory(),
            'jenis_dokumen_id' => JenisDokumen::factory(),
            'nomor' => fake()->numerify('DOK-####'),
            'tanggal_terbit' => '2024-01-01',
        ];
    }
}

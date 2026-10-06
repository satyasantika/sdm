<?php

namespace Database\Factories;

use App\Models\JenisSertifikasi;
use App\Models\Pegawai;
use App\Models\Sertifikasi;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Sertifikasi>
 */
class SertifikasiFactory extends Factory
{
    public function definition(): array
    {
        return [
            'pegawai_id' => Pegawai::factory(),
            'jenis_sertifikasi_id' => JenisSertifikasi::factory(),
            'nama' => 'Sertifikat '.fake()->words(2, true),
            'tanggal_terbit' => fake()->dateTimeBetween('-5 years', '-1 year')->format('Y-m-d'),
        ];
    }
}

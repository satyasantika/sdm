<?php

namespace Database\Factories;

use App\Models\Pegawai;
use App\Models\Pelatihan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Pelatihan>
 */
class PelatihanFactory extends Factory
{
    public function definition(): array
    {
        return [
            'pegawai_id' => Pegawai::factory(),
            'nama' => 'Pelatihan '.fake()->words(2, true),
            'jenis' => 'pelatihan',
            'tanggal_mulai' => '2025-03-01',
            'jumlah_jam' => 16,
        ];
    }
}

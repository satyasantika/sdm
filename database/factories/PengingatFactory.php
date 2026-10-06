<?php

namespace Database\Factories;

use App\Models\Pegawai;
use App\Models\Pengingat;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Pengingat>
 */
class PengingatFactory extends Factory
{
    public function definition(): array
    {
        $pegawai = Pegawai::factory();

        return [
            'pegawai_id' => $pegawai,
            'jenis' => 'kenaikan_pangkat',
            'referensi_tabel' => 'pegawai',
            'referensi_id' => $pegawai,
            'tanggal_jatuh_tempo' => now()->addDays(30)->toDateString(),
            'status' => 'aktif',
        ];
    }
}

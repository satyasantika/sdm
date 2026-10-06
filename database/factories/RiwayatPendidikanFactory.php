<?php

namespace Database\Factories;

use App\Models\JenjangPendidikan;
use App\Models\Pegawai;
use App\Models\RiwayatPendidikan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RiwayatPendidikan>
 */
class RiwayatPendidikanFactory extends Factory
{
    public function definition(): array
    {
        return [
            'pegawai_id' => Pegawai::factory(),
            'jenjang_pendidikan_id' => JenjangPendidikan::factory(),
            'nama_pt' => 'Universitas '.fake()->city(),
            'negara' => 'Indonesia',
            'tahun_masuk' => 2008,
            'tahun_lulus' => 2012,
        ];
    }
}

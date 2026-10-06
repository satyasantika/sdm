<?php

namespace Database\Factories;

use App\Models\Bkd;
use App\Models\Pegawai;
use App\Models\Semester;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Bkd>
 */
class BkdFactory extends Factory
{
    public function definition(): array
    {
        return [
            'pegawai_id' => Pegawai::factory(),
            'semester_id' => Semester::factory(),
            'sks_pendidikan' => 8,
            'sks_penelitian' => 3,
            'sks_pengabdian' => 2,
            'sks_penunjang' => 1,
            'total_sks' => 14,
            'kesimpulan' => 'memenuhi',
            'sumber' => 'manual',
        ];
    }
}

<?php

namespace Database\Factories;

use App\Models\JumlahMahasiswaProdi;
use App\Models\Prodi;
use App\Models\Semester;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<JumlahMahasiswaProdi>
 */
class JumlahMahasiswaProdiFactory extends Factory
{
    public function definition(): array
    {
        return [
            'prodi_id' => Prodi::factory(),
            'semester_id' => Semester::factory(),
            'jumlah_mahasiswa_aktif' => 250,
            'sumber' => 'PDDIKTI per 31 Oktober',
        ];
    }
}

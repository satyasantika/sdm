<?php

namespace Database\Factories;

use App\Enums\JenisTautan;
use App\Enums\PenyediaBerkas;
use App\Models\Pegawai;
use App\Models\TautanBerkas;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TautanBerkas>
 */
class TautanBerkasFactory extends Factory
{
    public function definition(): array
    {
        $pegawai = Pegawai::factory();

        return [
            'pemilik_type' => 'pegawai',
            'pemilik_id' => $pegawai,
            'pegawai_id' => $pegawai,
            'jenis' => JenisTautan::Lainnya,
            'url' => 'https://drive.google.com/file/d/'.fake()->regexify('[A-Za-z0-9_-]{28}').'/view',
            'penyedia' => PenyediaBerkas::GoogleDrive,
            'is_sensitif' => false,
        ];
    }
}

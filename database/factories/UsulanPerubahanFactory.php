<?php

namespace Database\Factories;

use App\Models\Pegawai;
use App\Models\User;
use App\Models\UsulanPerubahan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UsulanPerubahan>
 */
class UsulanPerubahanFactory extends Factory
{
    public function definition(): array
    {
        $pegawai = Pegawai::factory();

        return [
            'pegawai_id' => $pegawai,
            'diajukan_oleh' => User::factory(),
            'jenis' => 'ubah_biodata',
            'target_tabel' => 'pegawai',
            'data_baru' => ['no_hp' => '081200000000'],
            'status' => 'draf',
        ];
    }
}

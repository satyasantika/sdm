<?php

namespace Database\Factories;

use App\Enums\JenisKelamin;
use App\Enums\JenisPegawai;
use App\Models\JabatanFungsional;
use App\Models\Pegawai;
use App\Models\Prodi;
use App\Models\StatusKepegawaian;
use App\Models\UnitKerja;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Data palsu: NIK acak 16 digit bukan NIK nyata.
 *
 * @extends Factory<Pegawai>
 */
class PegawaiFactory extends Factory
{
    public function definition(): array
    {
        return [
            'jenis_pegawai' => JenisPegawai::Dosen,
            'gelar_depan' => null,
            'nama' => fake()->name(),
            'gelar_belakang' => fake()->randomElement(['M.Pd.', 'S.Pd., M.Pd.', 'M.Si.', null]),
            'nip' => fake()->unique()->numerify('##################'),
            'nidn' => fake()->unique()->numerify('##########'),
            'nuptk' => fake()->unique()->numerify('################'),
            'nik' => fake()->unique()->numerify('################'),
            'npwp' => fake()->numerify('##.###.###.#-###.###'),
            'nama_bank' => 'Bank Contoh',
            'nomor_rekening' => fake()->numerify('############'),
            'tempat_lahir' => fake()->city(),
            'tanggal_lahir' => fake()->dateTimeBetween('-60 years', '-28 years')->format('Y-m-d'),
            'jenis_kelamin' => fake()->randomElement(JenisKelamin::cases()),
            'agama' => 'Islam',
            'status_perkawinan' => 'kawin',
            'alamat' => fake()->address(),
            'no_hp' => fake()->numerify('08##########'),
            'email_pribadi' => fake()->unique()->safeEmail(),
            'email_unsil' => fake()->unique()->userName().'@unsil.ac.id',
            'status_kepegawaian_id' => StatusKepegawaian::factory(),
            'prodi_id' => Prodi::factory(),
            'unit_kerja_id' => null,
            'tmt_masuk' => fake()->dateTimeBetween('-20 years', '-1 year')->format('Y-m-d'),
            'status_aktif' => 'aktif',
        ];
    }

    public function dosen(): static
    {
        return $this->state(fn () => ['jenis_pegawai' => JenisPegawai::Dosen]);
    }

    public function tendik(): static
    {
        return $this->state(fn () => [
            'jenis_pegawai' => JenisPegawai::Tendik,
            'nidn' => null,
            'prodi_id' => null,
            'unit_kerja_id' => UnitKerja::factory(),
        ]);
    }

    public function profesor(): static
    {
        return $this->state(fn () => [
            'jabatan_fungsional_id' => JabatanFungsional::firstOrCreate(
                ['kode' => 'profesor'],
                ['nama' => 'Profesor', 'kelompok' => 'dosen', 'urutan' => 4, 'is_puncak' => true, 'is_aktif' => true],
            )->id,
        ]);
    }
}

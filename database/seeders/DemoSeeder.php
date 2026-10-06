<?php

namespace Database\Seeders;

use App\Enums\JenisKelamin;
use App\Enums\JenisPegawai;
use App\Enums\Peran;
use App\Models\JabatanFungsional;
use App\Models\Pegawai;
use App\Models\Prodi;
use App\Models\StatusKepegawaian;
use App\Models\UnitKerja;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Data demo untuk lingkungan local/testing saja. Seluruh data fiktif (faker id_ID);
 * NIK acak bukan NIK nyata. Kata sandi akun demo dari SEED_DEMO_PASSWORD.
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            logger()->warning('DemoSeeder hanya untuk lingkungan local/testing.');

            return;
        }

        $sandi = config('sdm.demo_password');
        if (blank($sandi)) {
            logger()->warning('SEED_DEMO_PASSWORD kosong; akun demo tidak dibuat.');

            return;
        }

        $pmat = Prodi::firstWhere('kode', 'PMAT');
        $pbio = Prodi::firstWhere('kode', 'PBIO');

        $akun = [
            ['superadmin', Peran::SuperAdmin, null],
            ['kepegawaian', Peran::AdminKepegawaian, null],
            ['adminpmat', Peran::AdminProdi, $pmat],
            ['adminpbio', Peran::AdminProdi, $pbio],
            ['dekan', Peran::Pimpinan, null],
        ];
        foreach ($akun as [$nama, $peran, $prodi]) {
            $user = User::updateOrCreate(['email' => $nama.'@unsil.ac.id'], [
                'name' => ucfirst($nama).' Demo', 'password' => $sandi, 'is_aktif' => true,
                'email_verified_at' => now(), 'prodi_id' => $prodi?->id,
            ]);
            $user->syncRoles([$peran->value]);
        }

        $status = StatusKepegawaian::all()->keyBy('kode');
        $jabatan = JabatanFungsional::where('kelompok', 'dosen')->get();
        $prodiAktif = Prodi::where('is_aktif', true)->get();
        $unit = UnitKerja::where('jenis', 'subbagian')->get();

        for ($i = 1; $i <= 40; $i++) {
            $dosen = $i <= 30;
            $statusKode = fake()->randomElement(['pns', 'pns', 'pppk', 'non-asn-tetap-blu', 'non-asn-kontrak']);

            Pegawai::updateOrCreate(['kode_eksternal' => 'DEMO-'.str_pad((string) $i, 3, '0', STR_PAD_LEFT)], [
                'jenis_pegawai' => $dosen ? JenisPegawai::Dosen : JenisPegawai::Tendik,
                'nama' => fake('id_ID')->name(),
                'gelar_belakang' => $dosen ? fake()->randomElement(['M.Pd.', 'M.Si.', 'S.Pd., M.Pd.']) : null,
                'nip' => fake()->unique()->numerify('19##########200###'),
                'nidn' => $dosen ? fake()->unique()->numerify('04########') : null,
                'nik' => fake()->unique()->numerify('32##############'),
                'tempat_lahir' => fake('id_ID')->city(),
                'tanggal_lahir' => fake()->dateTimeBetween('-60 years', '-27 years')->format('Y-m-d'),
                'jenis_kelamin' => fake()->randomElement(JenisKelamin::cases()),
                'status_kepegawaian_id' => $status[$statusKode]->id,
                'prodi_id' => $dosen ? ($i === 1 ? $pmat?->id : ($i === 2 ? $pbio?->id : $prodiAktif->random()->id)) : null,
                'unit_kerja_id' => $dosen ? null : $unit->random()->id,
                'jabatan_fungsional_id' => $dosen ? $jabatan->random()->id : null,
                'email_unsil' => "demo{$i}@unsil.ac.id",
                'tmt_masuk' => fake()->dateTimeBetween('-20 years', '-1 year')->format('Y-m-d'),
            ]);
        }

        $kaitan = [
            ['dosen1@unsil.ac.id', Peran::Dosen, 'DEMO-001'],
            ['dosen2@unsil.ac.id', Peran::Dosen, 'DEMO-002'],
            ['tendik1@unsil.ac.id', Peran::Tendik, 'DEMO-031'],
        ];
        foreach ($kaitan as [$email, $peran, $kode]) {
            $pegawai = Pegawai::firstWhere('kode_eksternal', $kode);
            $user = User::updateOrCreate(['email' => $email], [
                'name' => $pegawai->nama_bergelar, 'password' => $sandi, 'is_aktif' => true,
                'email_verified_at' => now(), 'prodi_id' => $pegawai->prodi_id,
            ]);
            $user->syncRoles([$peran->value]);
            $pegawai->update(['user_id' => $user->getKey(), 'email_unsil' => $email]);
        }
    }
}

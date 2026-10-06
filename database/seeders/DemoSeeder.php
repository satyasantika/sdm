<?php

namespace Database\Seeders;

use App\Actions\Riwayat\SimpanRiwayatJabatanFungsional;
use App\Actions\Riwayat\SimpanRiwayatKgb;
use App\Actions\Riwayat\SimpanRiwayatPangkat;
use App\Actions\Riwayat\SimpanRiwayatPendidikan;
use App\Enums\JenisKelamin;
use App\Enums\JenisPegawai;
use App\Enums\Peran;
use App\Models\Golongan;
use App\Models\JabatanFungsional;
use App\Models\JenisJabatanStruktural;
use App\Models\JenisSertifikasi;
use App\Models\JenjangPendidikan;
use App\Models\Keluarga;
use App\Models\Pegawai;
use App\Models\Pelatihan;
use App\Models\Penghargaan;
use App\Models\Prodi;
use App\Models\RiwayatJabatanStruktural;
use App\Models\Sertifikasi;
use App\Models\StatusKepegawaian;
use App\Models\StudiLanjut;
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
                'email_unsil' => "demo{$i}@unsil.ac.id",
                'tmt_masuk' => fake()->dateTimeBetween('-20 years', '-1 year')->format('Y-m-d'),
            ]);
        }

        $oleh = User::firstWhere('email', 'superadmin@unsil.ac.id');
        foreach (Pegawai::where('kode_eksternal', 'like', 'DEMO-%')->with('statusKepegawaian')->get() as $pegawai) {
            $this->buatRiwayat($pegawai, $oleh);
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

    /** Contoh riwayat di semua tab; idempoten (dilewati bila pegawai sudah punya riwayat pendidikan). */
    private function buatRiwayat(Pegawai $pegawai, User $oleh): void
    {
        if ($pegawai->riwayatPendidikan()->exists()) {
            return;
        }

        $dosen = $pegawai->jenis_pegawai === JenisPegawai::Dosen;
        $status = $pegawai->statusKepegawaian;

        $jenjang = [['S1', 2008, 2012, 'S.Pd.'], ['S2', 2013, 2015, 'M.Pd.']];
        if ($dosen && fake()->boolean(40)) {
            $jenjang[] = ['S3', 2018, 2023, 'Dr.'];
        }

        foreach ($jenjang as [$kode, $masuk, $lulus, $gelar]) {
            app(SimpanRiwayatPendidikan::class)->handle($pegawai, [
                'jenjang_pendidikan_id' => JenjangPendidikan::firstWhere('kode', $kode)->id,
                'nama_pt' => 'Universitas Contoh', 'tahun_masuk' => $masuk, 'tahun_lulus' => $lulus, 'gelar' => $gelar,
            ]);
        }

        if ($dosen) {
            foreach ([['asisten-ahli', '2016-03-01'], ['lektor', '2020-03-01']] as [$kode, $tmt]) {
                app(SimpanRiwayatJabatanFungsional::class)->handle($pegawai, [
                    'jabatan_fungsional_id' => JabatanFungsional::firstWhere('kode', $kode)->id, 'tmt' => $tmt, 'nomor_sk' => "SK/JF/{$kode}",
                ], $oleh);
            }
        }

        if ($status->jenis_golongan !== null) {
            $jenis = $status->jenis_golongan;
            $golongan = Golongan::where('jenis', $jenis)->orderBy('urutan')->get();
            foreach ([6, 8, 10] as $i => $urutan) {
                $g = $golongan->firstWhere('urutan', $urutan) ?? $golongan->last();
                app(SimpanRiwayatPangkat::class)->handle($pegawai, [
                    'golongan_id' => $g->id, 'tmt' => (2012 + $i * 4).'-04-01', 'nomor_sk' => 'SK/GOL/'.($i + 1),
                    'jenis_kenaikan' => $i === 0 ? 'pengangkatan_pns' : 'reguler', 'masa_kerja_tahun' => $i * 4, 'masa_kerja_bulan' => 0,
                ]);
            }

            if ($status->berlaku_kgb) {
                app(SimpanRiwayatKgb::class)->handle($pegawai, [
                    'golongan_id' => ($golongan->firstWhere('urutan', 10) ?? $golongan->last())->id,
                    'tmt' => '2024-04-01', 'nomor_sk' => 'SK/KGB/1', 'gaji_pokok' => '4567800.50', 'masa_kerja_tahun' => 12, 'masa_kerja_bulan' => 0,
                ]);
            }
        }

        if ($dosen) {
            Sertifikasi::create([
                'pegawai_id' => $pegawai->id, 'jenis_sertifikasi_id' => JenisSertifikasi::firstWhere('kode', 'serdos')->id,
                'nama' => 'Sertifikat Pendidik Dosen', 'nomor_registrasi' => fake()->numerify('##########'), 'tanggal_terbit' => '2019-06-01',
            ]);
            Sertifikasi::create([
                'pegawai_id' => $pegawai->id, 'jenis_sertifikasi_id' => JenisSertifikasi::firstWhere('kode', 'kompetensi')->id,
                'nama' => 'Sertifikat Kompetensi Contoh', 'tanggal_terbit' => '2024-01-01', 'tanggal_kedaluwarsa' => now()->addDays(fake()->numberBetween(30, 400))->toDateString(),
            ]);
        }

        Penghargaan::create(['pegawai_id' => $pegawai->id, 'kategori' => 'penghargaan', 'nama' => 'Dosen Berprestasi Contoh', 'tingkat' => 'universitas', 'tanggal' => '2023-11-01']);
        Pelatihan::create(['pegawai_id' => $pegawai->id, 'nama' => 'Workshop Contoh', 'jenis' => 'workshop', 'tanggal_mulai' => '2025-02-10', 'jumlah_jam' => 16]);
        Keluarga::create(['pegawai_id' => $pegawai->id, 'hubungan' => 'anak', 'nama' => fake('id_ID')->name(), 'nik' => fake()->numerify('32##############'), 'tanggal_lahir' => '2015-05-05']);

        if (fake()->boolean(25)) {
            RiwayatJabatanStruktural::create([
                'pegawai_id' => $pegawai->id, 'jenis_jabatan_struktural_id' => JenisJabatanStruktural::inRandomOrder()->value('id'),
                'tmt_mulai' => '2023-03-01', 'nomor_sk' => 'SK/STR/1',
            ]);
        }

        if ($dosen && fake()->boolean(15)) {
            StudiLanjut::create([
                'pegawai_id' => $pegawai->id, 'jenjang_pendidikan_id' => JenjangPendidikan::firstWhere('kode', 'S3')->id, 'jenis' => 'izin_belajar',
                'nama_pt' => 'Universitas Contoh', 'tanggal_mulai' => '2024-09-01', 'tanggal_selesai_rencana' => '2027-08-31', 'status' => 'berjalan',
            ]);
        }
    }
}

<?php

namespace Database\Seeders;

use App\Models\Bkd;
use App\Models\DokumenPegawai;
use App\Models\JabatanFungsional;
use App\Models\JenisDokumen;
use App\Models\JenisSertifikasi;
use App\Models\JenjangPendidikan;
use App\Models\Pegawai;
use App\Models\Prodi;
use App\Models\RiwayatPendidikan;
use App\Models\Semester;
use App\Models\Sertifikasi;
use App\Models\StatusKepegawaian;
use Illuminate\Database\Seeder;

/**
 * Data beban uji untuk mengukur kinerja (docs/KINERJA.md): 1.000 pegawai berikut riwayat pendidikan, sertifikasi,
 * 5.000 dokumen, dan 2 semester BKD. Hanya untuk lingkungan local/testing; seluruh data fiktif.
 *
 * Jalankan: php artisan db:seed --class=BebanUjiSeeder
 */
class BebanUjiSeeder extends Seeder
{
    public const JUMLAH_PEGAWAI = 1000;

    public const DOKUMEN_PER_PEGAWAI = 5;

    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            logger()->warning('BebanUjiSeeder hanya untuk lingkungan local/testing.');

            return;
        }

        if (Prodi::query()->doesntExist()) {
            $this->call([MasterProdiSeeder::class, MasterKepegawaianSeeder::class, MasterJabatanSeeder::class, MasterPendukungSeeder::class]);
        }

        $prodi = Prodi::query()->pluck('id')->all();
        $status = StatusKepegawaian::query()->pluck('id')->all();
        $jabfung = JabatanFungsional::query()->pluck('id')->all();
        $jenjang = JenjangPendidikan::query()->pluck('id')->all();
        $jenisSertifikasi = JenisSertifikasi::query()->pluck('id')->all();
        $jenisDokumen = JenisDokumen::query()->pluck('id')->all();

        $semester = collect(['20251', '20252'])->map(fn (string $kode) => Semester::query()->firstOrCreate(
            ['kode' => $kode],
            ['tahun_akademik' => '2025/2026', 'jenis' => str_ends_with($kode, '1') ? 'ganjil' : 'genap', 'is_aktif' => false],
        ));

        foreach (range(1, self::JUMLAH_PEGAWAI / 100) as $bagian) {
            Pegawai::factory()->count(100)->sequence(fn ($s) => [
                'prodi_id' => $prodi[$s->index % count($prodi)],
                'status_kepegawaian_id' => $status[$s->index % count($status)],
                'jabatan_fungsional_id' => $jabfung[$s->index % count($jabfung)],
            ])->create()->each(function (Pegawai $p) use ($jenjang, $jenisSertifikasi, $jenisDokumen, $semester): void {
                RiwayatPendidikan::factory()->create(['pegawai_id' => $p->id, 'jenjang_pendidikan_id' => $jenjang[array_rand($jenjang)], 'is_pendidikan_tertinggi' => true]);
                Sertifikasi::factory()->create(['pegawai_id' => $p->id, 'jenis_sertifikasi_id' => $jenisSertifikasi[array_rand($jenisSertifikasi)]]);

                foreach (range(1, self::DOKUMEN_PER_PEGAWAI) as $i) {
                    DokumenPegawai::factory()->create(['pegawai_id' => $p->id, 'jenis_dokumen_id' => $jenisDokumen[array_rand($jenisDokumen)]]);
                }

                foreach ($semester as $s) {
                    Bkd::factory()->create(['pegawai_id' => $p->id, 'semester_id' => $s->id]);
                }
            });
        }
    }
}

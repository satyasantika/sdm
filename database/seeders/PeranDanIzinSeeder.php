<?php

namespace Database\Seeders;

use App\Enums\Peran;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

class PeranDanIzinSeeder extends Seeder
{
    /** @var list<string> */
    public const IZIN = [
        'pengguna.kelola', 'audit.lihat', 'horizon.lihat', 'api.kelola-token', 'api.baca',
        'master.lihat', 'master.kelola', 'konfigurasi.kelola',
        'pegawai.lihat', 'pegawai.buat', 'pegawai.ubah', 'pegawai.hapus', 'pegawai.ekspor', 'pegawai.impor', 'pegawai.lihat-sensitif',
        'riwayat.lihat', 'riwayat-akademik.kelola', 'riwayat-kepegawaian.kelola',
        'keluarga.lihat', 'keluarga.kelola',
        'dokumen.lihat', 'dokumen.tambah', 'dokumen.kelola',
        'tautan-sensitif.lihat',
        'usulan.lihat', 'usulan.ajukan', 'usulan.verifikasi',
        'bkd.lihat', 'bkd.impor', 'bkd.ekspor',
        'pengingat.lihat', 'pengingat.kelola',
        'laporan.lihat', 'laporan.ekspor',
        'swalayan.akses',
    ];

    private const BUKAN_ADMIN_KEPEGAWAIAN = [
        'pengguna.kelola', 'horizon.lihat', 'api.kelola-token', 'api.baca', 'swalayan.akses',
    ];

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (self::IZIN as $izin) {
            Permission::firstOrCreate(['name' => $izin, 'guard_name' => 'web']);
        }

        foreach ($this->pemetaan() as $peran => $izin) {
            Role::firstOrCreate(['name' => $peran, 'guard_name' => 'web'])->syncPermissions($izin);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /** @return array<string, list<string>> */
    private function pemetaan(): array
    {
        return [
            Peran::SuperAdmin->value => self::IZIN,
            Peran::AdminKepegawaian->value => array_values(array_diff(self::IZIN, self::BUKAN_ADMIN_KEPEGAWAIAN)),
            Peran::AdminProdi->value => [
                'master.lihat', 'pegawai.lihat', 'pegawai.buat', 'pegawai.ubah', 'pegawai.ekspor',
                'riwayat.lihat', 'riwayat-akademik.kelola', 'dokumen.lihat', 'dokumen.tambah',
                'usulan.lihat', 'bkd.lihat', 'bkd.ekspor', 'pengingat.lihat', 'laporan.lihat', 'laporan.ekspor',
            ],
            Peran::Pimpinan->value => [
                'master.lihat', 'pegawai.lihat', 'pegawai.ekspor', 'riwayat.lihat', 'tautan-sensitif.lihat',
                'bkd.lihat', 'bkd.ekspor', 'pengingat.lihat', 'laporan.lihat', 'laporan.ekspor',
            ],
            Peran::Dosen->value => ['swalayan.akses', 'usulan.ajukan'],
            Peran::Tendik->value => ['swalayan.akses', 'usulan.ajukan'],
            Peran::KlienApi->value => ['api.baca'],
        ];
    }
}

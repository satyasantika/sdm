<?php

use App\Models\Pegawai;
use App\Models\RiwayatJabatanFungsional;
use App\Models\RiwayatPendidikan;
use App\Models\Sertifikasi;
use App\Models\User;
use Database\Seeders\DemoSeeder;
use Database\Seeders\KonfigurasiSeeder;
use Database\Seeders\MasterJabatanSeeder;
use Database\Seeders\MasterKepegawaianSeeder;
use Database\Seeders\MasterPendukungSeeder;
use Database\Seeders\MasterProdiSeeder;
use Database\Seeders\PeranDanIzinSeeder;

test('demo seeder membuat akun per peran dan 40 pegawai secara idempoten', function () {
    config(['sdm.demo_password' => 'sandi-demo-12345']);
    foreach ([PeranDanIzinSeeder::class, KonfigurasiSeeder::class, MasterProdiSeeder::class, MasterKepegawaianSeeder::class, MasterJabatanSeeder::class, MasterPendukungSeeder::class] as $seeder) {
        $this->seed($seeder);
    }

    $this->seed(DemoSeeder::class);
    $this->seed(DemoSeeder::class);

    expect(Pegawai::count())->toBe(40)
        ->and(Pegawai::dosen()->count())->toBe(30)
        ->and(User::where('email', 'adminpmat@unsil.ac.id')->first()->hasRole('admin-prodi'))->toBeTrue()
        ->and(Pegawai::firstWhere('kode_eksternal', 'DEMO-001')->user->email)->toBe('dosen1@unsil.ac.id')
        ->and(User::count())->toBe(8)
        ->and(RiwayatPendidikan::count())->toBeGreaterThanOrEqual(80)
        ->and(RiwayatJabatanFungsional::where('is_terkini', true)->count())->toBe(30)
        ->and(Pegawai::dosen()->whereNull('jabatan_fungsional_id')->count())->toBe(0)
        ->and(Sertifikasi::count())->toBe(60);
});

test('demo seeder tidak berjalan tanpa kata sandi', function () {
    config(['sdm.demo_password' => null]);

    $this->seed(DemoSeeder::class);

    expect(User::count())->toBe(0);
});

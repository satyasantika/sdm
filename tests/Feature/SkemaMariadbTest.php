<?php

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Support\Facades\Schema;

/** Perbaikan guard driver 'mysql' vs 'mariadb' lewat migrasi baru (migrasi lama yang sudah dirilis tidak diubah). */
it('memiliki indeks fulltext pada pegawai.nama di MariaDB', function () {
    $ada = collect(Schema::getIndexes('pegawai'))
        ->contains(fn (array $indeks) => $indeks['type'] === 'fulltext' && $indeks['columns'] === ['nama']);

    expect($ada)->toBeTrue();
});

it('menjalankan migrasi fulltext pegawai berulang kali tanpa galat', function () {
    $migrasi = require database_path('migrations/2026_10_17_100100_tambah_fulltext_nama_pegawai_mariadb.php');

    $migrasi->up();
    $migrasi->up();

    expect(collect(Schema::getIndexes('pegawai'))->where('type', 'fulltext')->where('columns', ['nama']))->toHaveCount(1);
});

it('menghapus izin horizon.* beserta keterkaitannya lewat migrasi data dan idempotent', function () {
    $izin = Permission::create(['name' => 'horizon.lihat', 'guard_name' => 'web']);
    $peran = Role::create(['name' => 'peran-uji-horizon', 'guard_name' => 'web']);
    $peran->givePermissionTo($izin);

    $migrasi = require database_path('migrations/2026_10_17_100000_hapus_izin_horizon.php');
    $migrasi->up();
    $migrasi->up();

    expect(Permission::where('name', 'like', 'horizon.%')->count())->toBe(0)
        ->and($peran->fresh()->permissions)->toHaveCount(0);
});

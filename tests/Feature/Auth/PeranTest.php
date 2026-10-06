<?php

use App\Enums\Peran;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\PeranDanIzinSeeder;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Permission;

beforeEach(fn () => $this->seed(PeranDanIzinSeeder::class));

function penggunaDengan(Peran $peran): User
{
    return tap(User::factory()->create(['email' => 'uji@unsil.ac.id']), fn (User $u) => $u->assignRole($peran->value));
}

test('seeder menghasilkan 7 peran dan semua izin', function () {
    expect(Role::count())->toBe(7)
        ->and(Permission::count())->toBe(count(PeranDanIzinSeeder::IZIN))
        ->and(Role::pluck('name')->sort()->values()->all())
        ->toBe(collect(Peran::cases())->map->value->sort()->values()->all());
});

test('seeder idempoten', function () {
    $this->seed(PeranDanIzinSeeder::class);

    expect(Role::count())->toBe(7)->and(Permission::count())->toBe(count(PeranDanIzinSeeder::IZIN));
});

test('admin-prodi hanya mengelola riwayat akademik', function () {
    $peran = Role::findByName(Peran::AdminProdi->value);

    expect($peran->hasPermissionTo('riwayat-akademik.kelola'))->toBeTrue()
        ->and($peran->hasPermissionTo('riwayat-kepegawaian.kelola'))->toBeFalse()
        ->and($peran->hasPermissionTo('pegawai.lihat-sensitif'))->toBeFalse();
});

test('admin-kepegawaian tidak memiliki izin sistem', function () {
    $peran = Role::findByName(Peran::AdminKepegawaian->value);

    expect($peran->hasPermissionTo('audit.lihat'))->toBeTrue()
        ->and($peran->hasPermissionTo('pengguna.kelola'))->toBeFalse()
        ->and($peran->hasPermissionTo('horizon.lihat'))->toBeFalse();
});

test('dosen saja ditolak membuka admin', function () {
    $this->actingAs(penggunaDengan(Peran::Dosen))->get('/admin')->assertForbidden();
});

test('pimpinan dapat membuka admin', function () {
    $this->actingAs(penggunaDengan(Peran::Pimpinan))->get('/admin')->assertOk();
});

test('super-admin lolos gate apa pun', function () {
    $user = penggunaDengan(Peran::SuperAdmin);

    expect(Gate::forUser($user)->allows('izin-yang-tidak-ada'))->toBeTrue();
});

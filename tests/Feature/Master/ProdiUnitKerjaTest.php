<?php

use App\Enums\Peran;
use App\Filament\Admin\Resources\Prodi\Pages\CreateProdi;
use App\Filament\Admin\Resources\Prodi\ProdiResource;
use App\Filament\Admin\Resources\Users\Pages\CreateUser;
use App\Models\Aktivitas;
use App\Models\Prodi;
use App\Models\Role;
use App\Models\UnitKerja;
use App\Models\User;
use Database\Seeders\MasterProdiSeeder;
use Database\Seeders\PeranDanIzinSeeder;
use Livewire\Livewire;

beforeEach(fn () => $this->seed(PeranDanIzinSeeder::class));

function pengguna(Peran $peran): User
{
    return tap(User::factory()->create([
        'email' => $peran->value.'@unsil.ac.id',
        'app_authentication_secret' => 'ABCDEFGHIJKLMNOP',
    ]), fn (User $u) => $u->assignRole($peran->value));
}

test('admin-kepegawaian dapat membuat prodi', function () {
    $this->actingAs(pengguna(Peran::AdminKepegawaian));

    Livewire::test(CreateProdi::class)
        ->fillForm(['kode' => 'PFIS', 'nama' => 'Pendidikan Fisika', 'jenjang' => 'S1', 'is_aktif' => true])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Prodi::where('kode', 'PFIS')->exists())->toBeTrue();
});

test('admin-prodi hanya dapat melihat dan tidak bisa membuat prodi', function () {
    $this->actingAs(pengguna(Peran::AdminProdi));

    $this->get(ProdiResource::getUrl('index'))->assertOk();
    $this->get(ProdiResource::getUrl('create'))->assertForbidden();
});

test('kode prodi ganda ditolak', function () {
    Prodi::factory()->create(['kode' => 'PMAT']);
    $this->actingAs(pengguna(Peran::AdminKepegawaian));

    Livewire::test(CreateProdi::class)
        ->fillForm(['kode' => 'PMAT', 'nama' => 'Ganda', 'jenjang' => 'S1'])
        ->call('create')
        ->assertHasFormErrors(['kode' => 'unique']);
});

test('user admin-prodi tanpa prodi ditolak validasi', function () {
    $this->actingAs(pengguna(Peran::SuperAdmin));
    $roleId = Role::where('name', Peran::AdminProdi->value)->value('id');

    Livewire::test(CreateUser::class)
        ->fillForm([
            'name' => 'Admin Prodi Baru', 'email' => 'baru@unsil.ac.id', 'password' => 'sandi-panjang-123',
            'roles' => [$roleId], 'prodi_id' => null,
        ])
        ->call('create')
        ->assertHasFormErrors(['prodi_id' => 'required']);
});

test('aktivitas tercatat saat prodi diubah', function () {
    $prodi = Prodi::factory()->create(['nama' => 'Nama Lama']);

    $prodi->update(['nama' => 'Nama Baru']);

    $log = Aktivitas::where('subject_id', $prodi->id)->where('event', 'updated')->first();
    expect($log)->not->toBeNull()->and(json_encode($log->attribute_changes))->toContain('Nama Baru');
});

test('seeder prodi idempoten dan membuat unit kerja', function () {
    $this->seed(MasterProdiSeeder::class);
    $this->seed(MasterProdiSeeder::class);

    expect(Prodi::count())->toBe(5)->and(UnitKerja::where('jenis', 'subbagian')->count())->toBe(3);
});

test('hanya super-admin yang dapat menghapus permanen prodi', function () {
    $prodi = Prodi::factory()->create();

    expect(pengguna(Peran::AdminKepegawaian)->can('forceDelete', $prodi))->toBeFalse()
        ->and(pengguna(Peran::SuperAdmin)->can('forceDelete', $prodi))->toBeTrue();
});

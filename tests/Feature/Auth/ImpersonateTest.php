<?php

use App\Enums\Peran;
use App\Filament\Admin\Resources\Users\Pages\ListUsers;
use App\Models\Aktivitas;
use App\Models\Prodi;
use App\Models\User;
use Database\Seeders\PeranDanIzinSeeder;
use Livewire\Livewire;
use STS\FilamentImpersonate\Facades\Impersonation;

beforeEach(function () {
    $this->seed(PeranDanIzinSeeder::class);
});

function akunPeran(Peran $peran, array $atribut = []): User
{
    return tap(User::factory()->create(array_merge([
        'email' => $peran->value.fake()->unique()->numerify('###').'@unsil.ac.id',
    ], $atribut)), fn (User $u) => $u->assignRole($peran->value));
}

test('super-admin dapat menyamar sebagai dosen dan kembali ke akun asli', function () {
    $prodi = Prodi::factory()->create();
    $superAdmin = akunPeran(Peran::SuperAdmin);
    $dosen = akunPeran(Peran::Dosen, ['prodi_id' => $prodi->id]);

    expect($superAdmin->canImpersonate())->toBeTrue()
        ->and($dosen->canBeImpersonated())->toBeTrue();

    $this->actingAs($superAdmin);

    Impersonation::enter($superAdmin, $dosen, 'web');

    expect(auth()->id())->toBe($dosen->id)
        ->and(Impersonation::isImpersonating())->toBeTrue();

    session(['impersonate.back_to' => '/admin']);
    $this->get('/filament-impersonate/leave')->assertRedirect('/admin');

    expect(auth()->id())->toBe($superAdmin->id)
        ->and(Impersonation::isImpersonating())->toBeFalse();
});

test('super-admin tidak dapat menyamar sesama super-admin atau klien-api', function () {
    $superAdminLain = akunPeran(Peran::SuperAdmin);
    $klienApi = akunPeran(Peran::KlienApi);

    expect($superAdminLain->canBeImpersonated())->toBeFalse()
        ->and($klienApi->canBeImpersonated())->toBeFalse();
});

test('admin-kepegawaian tidak dapat memulai impersonate', function () {
    $adminKepegawaian = akunPeran(Peran::AdminKepegawaian);

    expect($adminKepegawaian->canImpersonate())->toBeFalse();
});

test('tombol impersonate tampil di tabel pengguna untuk super-admin', function () {
    $dosen = akunPeran(Peran::Dosen);

    $this->actingAs(akunPeran(Peran::SuperAdmin));
    Livewire::test(ListUsers::class)->assertTableActionVisible('impersonate', $dosen->getKey());
});

test('tombol impersonate tidak tampil pada baris diri sendiri', function () {
    $superAdmin = akunPeran(Peran::SuperAdmin);
    $this->actingAs($superAdmin);

    Livewire::test(ListUsers::class)->assertTableActionHidden('impersonate', $superAdmin->getKey());
});

test('rute leave impersonate wajib login', function () {
    $this->get('/filament-impersonate/leave')->assertRedirect(route('filament.admin.auth.login'));
});

test('impersonate yang sedang berjalan mencatat aktivitas masuk dan keluar', function () {
    $superAdmin = akunPeran(Peran::SuperAdmin);
    $dosen = akunPeran(Peran::Dosen);
    $this->actingAs($superAdmin);

    Impersonation::enter($superAdmin, $dosen, 'web');
    session(['impersonate.back_to' => '/admin']);
    $this->get('/filament-impersonate/leave');

    expect(Aktivitas::where('event', 'impersonate-masuk')->where('subject_id', $dosen->id)->exists())->toBeTrue()
        ->and(Aktivitas::where('event', 'impersonate-keluar')->where('subject_id', $dosen->id)->exists())->toBeTrue();
});

<?php

use App\Enums\Peran;
use App\Filament\Admin\Resources\Users\Pages\EditUser;
use App\Filament\Admin\Resources\Users\UserResource;
use App\Models\User;
use Database\Seeders\PeranDanIzinSeeder;
use Filament\Auth\Pages\Login;
use Livewire\Livewire;

beforeEach(fn () => $this->seed(PeranDanIzinSeeder::class));

function akun(Peran $peran, array $atribut = []): User
{
    return tap(User::factory()->create($atribut + ['email' => $peran->value.'@unsil.ac.id']), fn (User $u) => $u->assignRole($peran->value));
}

test('admin-kepegawaian tanpa mfa diarahkan ke halaman pengaktifan mfa', function () {
    $this->actingAs(akun(Peran::AdminKepegawaian))->get('/admin')
        ->assertRedirect(url('/admin/profile'));
});

test('super-admin tanpa mfa juga diarahkan', function () {
    $this->actingAs(akun(Peran::SuperAdmin))->get('/admin')->assertRedirect();
});

test('admin-prodi tanpa mfa tetap bisa masuk dasbor', function () {
    $this->actingAs(akun(Peran::AdminProdi))->get('/admin')->assertOk();
});

test('login gagal ke-6 dalam satu menit ditolak', function () {
    $user = akun(Peran::AdminProdi, ['password' => 'sandi-benar-123']);

    foreach (range(1, 5) as $i) {
        Livewire::test(Login::class)
            ->fillForm(['email' => $user->email, 'password' => 'salah'])
            ->call('authenticate')
            ->assertHasErrors();
    }

    Livewire::test(Login::class)
        ->fillForm(['email' => $user->email, 'password' => 'sandi-benar-123'])
        ->call('authenticate');

    $this->assertGuest();
});

test('admin-kepegawaian tidak bisa membuka daftar pengguna tetapi super-admin bisa', function () {
    $mfa = ['app_authentication_secret' => 'ABCDEFGHIJKLMNOP'];

    $this->actingAs(akun(Peran::AdminKepegawaian, $mfa))->get(UserResource::getUrl('index'))->assertForbidden();
    $this->actingAs(akun(Peran::SuperAdmin, $mfa))->get(UserResource::getUrl('index'))->assertOk();
});

test('super-admin tidak bisa menonaktifkan akunnya sendiri', function () {
    $admin = akun(Peran::SuperAdmin, ['app_authentication_secret' => 'ABCDEFGHIJKLMNOP']);
    $this->actingAs($admin);

    Livewire::test(EditUser::class, ['record' => $admin->getKey()])
        ->fillForm(['is_aktif' => false])
        ->call('save');

    expect($admin->fresh()->is_aktif)->toBeTrue()
        ->and(fn () => $admin->delete())->toThrow(LogicException::class);
});

test('horizon 403 untuk admin-kepegawaian dan terbuka untuk super-admin di production', function () {
    $this->app['env'] = 'production';

    $this->actingAs(akun(Peran::AdminKepegawaian))->get('/horizon')->assertForbidden();
    $this->actingAs(akun(Peran::SuperAdmin))->get('/horizon')->assertOk();
});

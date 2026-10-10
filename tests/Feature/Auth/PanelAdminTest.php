<?php

use App\Enums\Peran;
use App\Filament\Auth\UbahProfil;
use App\Models\User;
use Database\Seeders\PeranDanIzinSeeder;
use Database\Seeders\SuperAdminSeeder;
use Illuminate\Auth\Events\OtherDeviceLogout;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

beforeEach(fn () => $this->seed(PeranDanIzinSeeder::class));

test('tamu diarahkan ke halaman login admin', function () {
    $this->get('/admin')->assertRedirect('/admin/login');
});

test('halaman login berbahasa indonesia dengan merek sdm fkip unsil', function () {
    $this->get('/admin/login')->assertOk()->assertSee('SDM FKIP Unsil')->assertSee('Masuk');
});

test('user aktif ber-email unsil dapat membuka admin', function () {
    $user = tap(User::factory()->create(['email' => 'budi@unsil.ac.id']), fn ($u) => $u->assignRole(Peran::AdminProdi->value));

    $this->actingAs($user)->get('/admin')->assertOk()->assertSee('v'.config('app.version'));
});

test('user tidak aktif ditolak dengan 403', function () {
    $user = tap(User::factory()->create(['email' => 'budi@unsil.ac.id', 'is_aktif' => false]), fn ($u) => $u->assignRole(Peran::AdminProdi->value));

    $this->actingAs($user)->get('/admin')->assertForbidden();
});

test('user ber-email di luar unsil ditolak di lingkungan non-local', function () {
    $user = tap(User::factory()->create(['email' => 'budi@contoh.com']), fn ($u) => $u->assignRole(Peran::AdminProdi->value));

    $this->actingAs($user)->get('/admin')->assertForbidden();
});

test('last_login_at terisi setelah login', function () {
    $user = tap(User::factory()->create(['email' => 'budi@unsil.ac.id']), fn ($u) => $u->assignRole(Peran::AdminProdi->value));
    expect($user->last_login_at)->toBeNull();

    auth()->login($user);

    expect($user->fresh()->last_login_at)->not->toBeNull();
});

test('seeder super admin membaca kredensial dari konfigurasi', function () {
    config([
        'sdm.superadmin.email' => 'root@unsil.ac.id',
        'sdm.superadmin.password' => 'rahasia-uji-123',
    ]);

    $this->seed(SuperAdminSeeder::class);

    expect(User::where('email', 'root@unsil.ac.id')->exists())->toBeTrue();
});

test('pengguna dengan wajib_ganti_sandi diarahkan ke profil', function () {
    $user = tap(User::factory()->create(['email' => 'baru@unsil.ac.id', 'wajib_ganti_sandi' => true]), fn ($u) => $u->assignRole(Peran::AdminProdi->value));

    $this->actingAs($user)->get('/admin')->assertRedirect('/admin/profile');
});

test('mengganti sandi di profil admin mencabut kewajiban dan mengeluarkan perangkat lain', function () {
    $user = tap(User::factory()->create(['email' => 'budi@unsil.ac.id', 'wajib_ganti_sandi' => true]), fn ($u) => $u->assignRole(Peran::AdminProdi->value));
    $lama = $user->password;
    Event::fake([OtherDeviceLogout::class]);

    $this->actingAs($user);

    Livewire::test(UbahProfil::class)
        ->fillForm([
            'name' => $user->name,
            'email' => $user->email,
            'password' => 'sandi-baru-123',
            'passwordConfirmation' => 'sandi-baru-123',
            'currentPassword' => 'password',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $user->refresh();
    expect($user->wajib_ganti_sandi)->toBeFalse()
        ->and($user->password)->not->toBe($lama)
        ->and(Hash::check('sandi-baru-123', $user->password))->toBeTrue();
    Event::assertDispatched(OtherDeviceLogout::class);

    $this->get('/admin')->assertOk();
});

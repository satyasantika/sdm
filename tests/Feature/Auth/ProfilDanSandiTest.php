<?php

use App\Enums\Peran;
use App\Models\Pegawai;
use App\Models\PersetujuanPrivasi;
use App\Models\User;
use App\Support\Konfigurasi;
use Database\Seeders\KonfigurasiSeeder;
use Database\Seeders\PeranDanIzinSeeder;
use Filament\Auth\Pages\EditProfile;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(PeranDanIzinSeeder::class);
    $this->seed(KonfigurasiSeeder::class);
});

const SANDI_LAMA = 'Sandi-Lama-123!';
const SANDI_BARU = 'Sandi-Baru-456!x';

/** Akun siap pakai untuk panel `admin` (dengan MFA aktif) atau `swalayan` (pegawai + persetujuan privasi). */
function akunProfil(Peran $peran): User
{
    $user = User::factory()->create([
        'email' => $peran->value.'@unsil.ac.id',
        'password' => SANDI_LAMA,
        'app_authentication_secret' => 'ABCDEFGHIJKLMNOP',
    ]);
    $user->assignRole($peran->value);

    if (in_array($peran, [Peran::Dosen, Peran::Tendik], true)) {
        $peran === Peran::Dosen ? Pegawai::factory()->dosen()->create(['user_id' => $user->id]) : Pegawai::factory()->tendik()->create(['user_id' => $user->id]);
        PersetujuanPrivasi::create(['user_id' => $user->id, 'versi' => Konfigurasi::get('versi_kebijakan_privasi'), 'disetujui_at' => now()]);
    }

    return $user;
}

function panelProfil(Peran $peran): string
{
    return in_array($peran, [Peran::Dosen, Peran::Tendik], true) ? 'swalayan' : 'admin';
}

dataset('peran-login', [
    Peran::SuperAdmin,
    Peran::AdminKepegawaian,
    Peran::AdminProdi,
    Peran::Pimpinan,
    Peran::Dosen,
    Peran::Tendik,
]);

test('setiap peran yang bisa masuk panel dapat membuka halaman profil', function (Peran $peran) {
    $jalur = panelProfil($peran) === 'swalayan' ? 'saya' : 'admin';

    $this->actingAs(akunProfil($peran))->get(url("/{$jalur}/profile"))->assertOk()->assertSee('Kata sandi baru', false);
})->with('peran-login');

test('setiap peran dapat mengubah nama dan kata sandi dengan kata sandi saat ini', function (Peran $peran) {
    $user = akunProfil($peran);
    $this->actingAs($user);
    Filament::setCurrentPanel(Filament::getPanel(panelProfil($peran)));

    Livewire::test(EditProfile::class)
        ->fillForm([
            'name' => 'Nama Diperbarui',
            'password' => SANDI_BARU,
            'passwordConfirmation' => SANDI_BARU,
            'currentPassword' => SANDI_LAMA,
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $user->refresh();

    expect($user->name)->toBe('Nama Diperbarui')
        ->and(Hash::check(SANDI_BARU, $user->password))->toBeTrue()
        ->and(Hash::check(SANDI_LAMA, $user->password))->toBeFalse();
})->with('peran-login');

test('ganti kata sandi ditolak bila kata sandi saat ini salah', function () {
    $user = akunProfil(Peran::AdminProdi);
    $this->actingAs($user);
    Filament::setCurrentPanel(Filament::getPanel('admin'));

    Livewire::test(EditProfile::class)
        ->fillForm(['password' => SANDI_BARU, 'passwordConfirmation' => SANDI_BARU, 'currentPassword' => 'salah-total'])
        ->call('save')
        ->assertHasFormErrors(['currentPassword']);

    expect(Hash::check(SANDI_LAMA, $user->fresh()->password))->toBeTrue();
});

test('ganti kata sandi ditolak bila konfirmasi tidak sama atau terlalu pendek', function () {
    $user = akunProfil(Peran::Dosen);
    $this->actingAs($user);
    Filament::setCurrentPanel(Filament::getPanel('swalayan'));

    Livewire::test(EditProfile::class)
        ->fillForm(['password' => SANDI_BARU, 'passwordConfirmation' => 'tidak-sama-123', 'currentPassword' => SANDI_LAMA])
        ->call('save')
        ->assertHasFormErrors(['password']);

    Livewire::test(EditProfile::class)
        ->fillForm(['password' => 'pendek', 'passwordConfirmation' => 'pendek', 'currentPassword' => SANDI_LAMA])
        ->call('save')
        ->assertHasFormErrors(['password']);

    expect(Hash::check(SANDI_LAMA, $user->fresh()->password))->toBeTrue();
});

test('tamu diarahkan ke login saat membuka profil', function () {
    $this->get(url('/admin/profile'))->assertRedirect();
    $this->get(url('/saya/profile'))->assertRedirect();
});

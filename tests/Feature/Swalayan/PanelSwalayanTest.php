<?php

use App\Enums\Peran;
use App\Filament\Auth\UbahProfil;
use App\Filament\Swalayan\Pages\Beranda;
use App\Filament\Swalayan\Pages\PersetujuanPrivasi;
use App\Filament\Swalayan\Pages\ProfilSaya;
use App\Models\Aktivitas;
use App\Models\Pegawai;
use App\Models\PersetujuanPrivasi as Persetujuan;
use App\Models\User;
use App\Models\UsulanPerubahan;
use App\Support\Konfigurasi;
use Database\Seeders\KonfigurasiSeeder;
use Database\Seeders\PeranDanIzinSeeder;
use Filament\Actions\Testing\TestAction;
use Illuminate\Auth\Events\OtherDeviceLogout;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(PeranDanIzinSeeder::class);
    $this->seed(KonfigurasiSeeder::class);
});

/** @return array{0: User, 1: Pegawai} */
function dosenSwalayan(array $pegawai = []): array
{
    $user = User::factory()->create(['email' => 'dosen'.fake()->unique()->numerify('####').'@unsil.ac.id']);
    $user->assignRole(Peran::Dosen->value);

    return [$user, Pegawai::factory()->create($pegawai + ['user_id' => $user->id])];
}

function setujuiPrivasi(User $user, ?string $versi = null): void
{
    Persetujuan::create(['user_id' => $user->id, 'versi' => $versi ?? Konfigurasi::get('versi_kebijakan_privasi'), 'disetujui_at' => now()]);
}

test('dosen tanpa persetujuan diarahkan ke halaman persetujuan lalu dapat membuka beranda', function () {
    [$user] = dosenSwalayan();

    $this->actingAs($user)->get('/saya')->assertRedirect(PersetujuanPrivasi::getUrl(panel: 'swalayan'));

    Livewire::actingAs($user)->test(PersetujuanPrivasi::class)
        ->call('setujui')
        ->assertNoRedirect();
    expect(Persetujuan::count())->toBe(0);

    Livewire::actingAs($user)->test(PersetujuanPrivasi::class)
        ->set('setuju', true)
        ->call('setujui')
        ->assertRedirect(Beranda::getUrl(panel: 'swalayan'));

    $persetujuan = Persetujuan::first();
    expect($persetujuan->versi)->toBe('2026.1')->and($persetujuan->user_id)->toBe($user->id)
        ->and(Aktivitas::where('description', 'menyetujui kebijakan privasi')->count())->toBe(1);

    $this->actingAs($user)->get('/saya')->assertOk();
});

test('menaikkan versi kebijakan memaksa persetujuan ulang', function () {
    [$user] = dosenSwalayan();
    setujuiPrivasi($user);
    $this->actingAs($user)->get('/saya')->assertOk();

    Konfigurasi::set('versi_kebijakan_privasi', '2026.2');

    $this->actingAs($user)->get('/saya')->assertRedirect(PersetujuanPrivasi::getUrl(panel: 'swalayan'));
});

test('halaman persetujuan merender markdown dengan aman', function () {
    [$user] = dosenSwalayan();
    Konfigurasi::set('teks_kebijakan_privasi', "# Judul Privasi\n\n<script>alert(1)</script>\n\n[x](javascript:alert(1))");

    $this->actingAs($user)->get(PersetujuanPrivasi::getUrl(panel: 'swalayan'))
        ->assertOk()->assertSee('Judul Privasi')->assertDontSee('<script>alert(1)</script>', false)->assertDontSee('javascript:alert', false);
});

test('dosen hanya melihat datanya sendiri', function () {
    [$user, $pegawai] = dosenSwalayan(['nama' => 'Siti Pemilik', 'gelar_belakang' => null]);
    Pegawai::factory()->create(['nama' => 'Budi Orang Lain', 'gelar_belakang' => null]);
    setujuiPrivasi($user);

    $this->actingAs($user)->get('/saya')->assertOk()->assertSee('Siti Pemilik')->assertDontSee('Budi Orang Lain');
    $this->actingAs($user)->get(ProfilSaya::getUrl(panel: 'swalayan'))->assertOk()->assertSee('Siti Pemilik')->assertDontSee('Budi Orang Lain');
});

test('admin-kepegawaian tanpa pegawai tidak dapat membuka saya', function () {
    $admin = User::factory()->create(['email' => 'kepeg@unsil.ac.id', 'app_authentication_secret' => 'ABCDEFGHIJKLMNOP']);
    $admin->assignRole(Peran::AdminKepegawaian->value);

    $this->actingAs($admin)->get('/saya')->assertForbidden();
});

test('user nonaktif atau tanpa izin swalayan tidak dapat membuka saya', function () {
    [$user] = dosenSwalayan();
    $user->update(['is_aktif' => false]);
    $this->actingAs($user)->get('/saya')->assertForbidden();

    $tanpaPegawai = User::factory()->create(['email' => 'tanpa@unsil.ac.id']);
    $tanpaPegawai->assignRole(Peran::Dosen->value);
    $this->actingAs($tanpaPegawai)->get('/saya')->assertForbidden();
});

test('tampil nik milik sendiri berhasil dan tercatat di log akses sensitif', function () {
    [$user, $pegawai] = dosenSwalayan(['nik' => '3278010101900001']);
    setujuiPrivasi($user);

    Livewire::actingAs($user)->test(ProfilSaya::class)
        ->assertSee('3278********0001')->assertDontSee('3278010101900001')
        ->mountAction(TestAction::make('tampil_nik')->schemaComponent('nik_tersamar'))
        ->assertActionDataSet(['nilai' => '3278010101900001']);

    $log = Aktivitas::where('log_name', 'akses-sensitif')->get();
    expect($log)->toHaveCount(1)->and($log->first()->causer_id)->toBe($user->id)->and($log->first()->properties->get('kolom'))->toBe('nik');
});

test('beranda menampilkan ringkasan dan usulan terakhir', function () {
    [$user, $pegawai] = dosenSwalayan(['nip' => '198501012010012001']);
    setujuiPrivasi($user);
    UsulanPerubahan::factory()->create(['pegawai_id' => $pegawai->id, 'diajukan_oleh' => $user->id, 'status' => 'diajukan']);

    $this->actingAs($user)->get('/saya')->assertOk()->assertSee('198501012010012001')->assertSee('Diajukan')->assertSee('Ubah biodata');
});

test('halaman root mengarahkan sesuai peran', function () {
    [$user] = dosenSwalayan();
    $admin = User::factory()->create(['email' => 'adm@unsil.ac.id', 'app_authentication_secret' => 'ABCDEFGHIJKLMNOP']);
    $admin->assignRole(Peran::AdminKepegawaian->value);

    $this->get('/')->assertOk()->assertSee('Panduan Pengguna');
    $this->actingAs($user)->get('/')->assertRedirect('/saya');
    $this->actingAs($admin)->get('/')->assertRedirect('/admin');
});

test('pengguna dengan wajib_ganti_sandi di panel swalayan diarahkan ke profil', function () {
    [$user] = dosenSwalayan();
    setujuiPrivasi($user);
    $user->update(['wajib_ganti_sandi' => true]);

    $this->actingAs($user)->get('/saya')->assertRedirect('/saya/profile');
});

test('mengganti sandi di profil swalayan mencabut kewajiban dan mengeluarkan perangkat lain', function () {
    [$user] = dosenSwalayan();
    setujuiPrivasi($user);
    $user->update(['wajib_ganti_sandi' => true]);
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

    $this->get('/saya')->assertOk();
});

<?php

use App\Actions\Api\BuatTokenKlienApi;
use App\Actions\Pegawai\TampilkanDataSensitif;
use App\Enums\Peran;
use App\Models\Pegawai;
use App\Models\TautanBerkas;
use App\Models\User;
use App\Support\BatasEkspor;
use Database\Seeders\KonfigurasiSeeder;
use Database\Seeders\PeranDanIzinSeeder;
use Filament\Actions\Action;
use Filament\Auth\Pages\Login;
use Filament\Support\Exceptions\Halt;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(PeranDanIzinSeeder::class);
    $this->seed(KonfigurasiSeeder::class);
    RateLimiter::clear('tampil-sensitif');
    Cache::flush();
});

function adminRl(): User
{
    return User::factory()->create(['email' => 'rl'.fake()->unique()->numerify('####').'@unsil.ac.id', 'app_authentication_secret' => 'ABCDEFGHIJKLMNOP'])->assignRole(Peran::AdminKepegawaian->value);
}

test('login dibatasi setelah 5 percobaan gagal per menit', function () {
    $user = User::factory()->create(['email' => 'korban@unsil.ac.id']);
    $komponen = Livewire::test(Login::class)->set('data.email', $user->email)->set('data.password', 'salah');

    foreach (range(1, 5) as $i) {
        $komponen->call('authenticate')->assertNotNotified();
    }
    $komponen->call('authenticate')->assertNotified();
    expect(auth()->check())->toBeFalse();
});

test('tampil data sensitif dibatasi 10 per menit', function () {
    $admin = adminRl();
    $pegawai = Pegawai::factory()->create(['nik' => '3278010101900001']);

    foreach (range(1, TampilkanDataSensitif::BATAS_PER_MENIT) as $i) {
        app(TampilkanDataSensitif::class)->handle($admin, $pegawai, 'nik');
    }

    expect(fn () => app(TampilkanDataSensitif::class)->handle($admin, $pegawai, 'nik'))->toThrow(ThrottleRequestsException::class);
});

test('ekspor dibatasi 5 per menit per pengguna', function () {
    $this->actingAs(adminRl());
    $hook = BatasEkspor::sebelum();
    $aksi = Action::make('ekspor');

    foreach (range(1, BatasEkspor::MAKS) as $i) {
        $hook($aksi);
    }

    expect(fn () => $hook($aksi))->toThrow(Halt::class);
});

test('buka tautan dibatasi 60 per menit dan api 60 per menit', function () {
    $admin = adminRl();
    $pegawai = Pegawai::factory()->create();
    $tautan = TautanBerkas::factory()->create(['pemilik_id' => $pegawai->id, 'pegawai_id' => $pegawai->id, 'is_sensitif' => false]);

    foreach (range(1, 60) as $i) {
        $this->actingAs($admin)->get(route('tautan.buka', $tautan))->assertRedirect();
    }
    $this->actingAs($admin)->get(route('tautan.buka', $tautan))->assertStatus(429);

    [, $polos] = app(BuatTokenKlienApi::class)->handle(User::factory()->create(['email' => 'sa@unsil.ac.id'])->assignRole(Peran::SuperAdmin->value), 'Rl', 'rl-1');
    foreach (range(1, 60) as $i) {
        $this->withToken($polos)->getJson('/api/v1/pejabat')->assertOk();
    }
    $this->withToken($polos)->getJson('/api/v1/pejabat')->assertStatus(429);
});

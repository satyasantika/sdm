<?php

use App\Enums\Peran;
use App\Events\KonfigurasiKepegawaianDiubah;
use App\Filament\Admin\Pages\Konfigurasi as HalamanKonfigurasi;
use App\Models\Aktivitas;
use App\Models\Prodi;
use App\Models\User;
use App\Support\Konfigurasi;
use Database\Seeders\KonfigurasiSeeder;
use Database\Seeders\PeranDanIzinSeeder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(PeranDanIzinSeeder::class);
    $this->seed(KonfigurasiSeeder::class);
    Cache::flush();
});

function akunKonfig(Peran $peran): User
{
    return tap(User::factory()->create([
        'email' => $peran->value.'@unsil.ac.id',
        'app_authentication_secret' => 'ABCDEFGHIJKLMNOP',
    ]), fn (User $u) => $u->assignRole($peran->value));
}

test('nilai konfigurasi terbaca dengan tipe yang benar', function () {
    expect(Konfigurasi::get('bup_dosen'))->toBe(65)
        ->and(Konfigurasi::get('tahap_pengingat_hari'))->toBe([90, 30, 7])
        ->and(Konfigurasi::get('versi_kebijakan_privasi'))->toBe('2026.1')
        ->and(Konfigurasi::get('syarat_unggul_sdm')['S1']['5_tahun']['min_dtps_doktor'])->toBe(2)
        ->and(Konfigurasi::get('tidak-ada', 'bawaan'))->toBe('bawaan');
});

test('set menghapus cache dan nilai baru terbaca', function () {
    expect(Konfigurasi::get('bup_dosen'))->toBe(65)->and(Cache::has('sdm:konfigurasi'))->toBeTrue();

    Konfigurasi::set('bup_dosen', 67, akunKonfig(Peran::SuperAdmin));

    expect(Cache::has('sdm:konfigurasi'))->toBeFalse()->and(Konfigurasi::get('bup_dosen'))->toBe(67);
});

test('perubahan konfigurasi tercatat di log audit', function () {
    Konfigurasi::set('bup_dosen', 66, akunKonfig(Peran::SuperAdmin));

    expect(Aktivitas::where('subject_type', App\Models\Konfigurasi::class)->where('event', 'updated')->exists())->toBeTrue();
});

test('seeder tidak menimpa nilai yang sudah diubah admin', function () {
    Konfigurasi::set('bup_dosen', 66);
    $this->seed(KonfigurasiSeeder::class);

    expect(Konfigurasi::get('bup_dosen'))->toBe(66);
});

test('halaman konfigurasi mengirim event saat bup diubah', function () {
    Event::fake([KonfigurasiKepegawaianDiubah::class]);
    $this->actingAs(akunKonfig(Peran::AdminKepegawaian));

    Livewire::test(HalamanKonfigurasi::class)
        ->set('data.bup_dosen', 66)
        ->call('simpan')
        ->assertHasNoFormErrors();

    Event::assertDispatched(KonfigurasiKepegawaianDiubah::class, fn ($e) => $e->kunci === ['bup_dosen']);
    expect(Konfigurasi::get('bup_dosen'))->toBe(66);
});

test('mengubah teks privasi saja tidak mengirim event hitung ulang', function () {
    Event::fake([KonfigurasiKepegawaianDiubah::class]);
    $this->actingAs(akunKonfig(Peran::AdminKepegawaian));

    Livewire::test(HalamanKonfigurasi::class)->set('data.versi_kebijakan_privasi', '2026.2')->call('simpan');

    Event::assertNotDispatched(KonfigurasiKepegawaianDiubah::class);
    expect(Konfigurasi::get('versi_kebijakan_privasi'))->toBe('2026.2');
});

test('admin-prodi mendapat 403 di halaman konfigurasi', function () {
    $this->actingAs(akunKonfig(Peran::AdminProdi))->get(HalamanKonfigurasi::getUrl())->assertForbidden();
    $this->actingAs(akunKonfig(Peran::AdminKepegawaian))->get(HalamanKonfigurasi::getUrl())->assertOk();
});

test('mengubah prodi menghapus cache master prodi', function () {
    Prodi::factory()->create(['nama' => 'Awal']);
    expect(Prodi::opsiAktif())->toHaveCount(1)->and(Cache::has('sdm:master:prodi'))->toBeTrue();

    Prodi::factory()->create(['nama' => 'Baru']);

    expect(Cache::has('sdm:master:prodi'))->toBeFalse()->and(Prodi::opsiAktif())->toHaveCount(2);
});

<?php

use App\Enums\JenisGolongan;
use App\Enums\Peran;
use App\Filament\Admin\Resources\Golongan\Pages\CreateGolongan;
use App\Filament\Admin\Resources\StatusKepegawaian\Pages\CreateStatusKepegawaian;
use App\Filament\Admin\Resources\StatusKepegawaian\StatusKepegawaianResource;
use App\Models\Golongan;
use App\Models\StatusKepegawaian;
use App\Models\User;
use Database\Seeders\MasterKepegawaianSeeder;
use Database\Seeders\PeranDanIzinSeeder;
use Livewire\Livewire;

beforeEach(fn () => $this->seed(PeranDanIzinSeeder::class));

function pegawaiAdmin(Peran $peran): User
{
    return tap(User::factory()->create([
        'email' => $peran->value.'@unsil.ac.id',
        'app_authentication_secret' => 'ABCDEFGHIJKLMNOP',
    ]), fn (User $u) => $u->assignRole($peran->value));
}

test('seeder menghasilkan 17 golongan pns dan 17 pppk serta 5 status', function () {
    $this->seed(MasterKepegawaianSeeder::class);

    expect(Golongan::where('jenis', JenisGolongan::Pns)->count())->toBe(17)
        ->and(Golongan::where('jenis', JenisGolongan::Pppk)->count())->toBe(17)
        ->and(StatusKepegawaian::count())->toBe(5);
});

test('seeder idempoten', function () {
    $this->seed(MasterKepegawaianSeeder::class);
    $this->seed(MasterKepegawaianSeeder::class);

    expect(Golongan::count())->toBe(34)->and(StatusKepegawaian::count())->toBe(5);
});

test('penanda status sesuai dokumen', function () {
    $this->seed(MasterKepegawaianSeeder::class);

    $pns = StatusKepegawaian::firstWhere('kode', 'pns');
    $kontrak = StatusKepegawaian::firstWhere('kode', 'non-asn-kontrak');

    expect($pns->berlaku_kenaikan_pangkat)->toBeTrue()
        ->and($kontrak->dihitung_dosen_tetap)->toBeFalse()
        ->and(StatusKepegawaian::firstWhere('kode', 'cpns')->berlaku_kgb)->toBeFalse();
});

test('label golongan III/c adalah penata', function () {
    $this->seed(MasterKepegawaianSeeder::class);

    expect(Golongan::where('kode', 'III/c')->first()->label)->toBe('III/c — Penata')
        ->and(Golongan::where('jenis', JenisGolongan::Pppk)->where('kode', 'IX')->first()->label)->toBe('IX');
});

test('admin-prodi tidak dapat mengubah status kepegawaian', function () {
    $status = StatusKepegawaian::factory()->create();
    $this->actingAs(pegawaiAdmin(Peran::AdminProdi));

    $this->get(StatusKepegawaianResource::getUrl('index'))->assertOk();
    $this->get(StatusKepegawaianResource::getUrl('edit', ['record' => $status]))->assertForbidden();
});

test('kode golongan sama pada jenis berbeda diperbolehkan, pada jenis sama ditolak', function () {
    Golongan::factory()->create(['jenis' => 'pns', 'kode' => 'X']);
    $this->actingAs(pegawaiAdmin(Peran::AdminKepegawaian));

    Livewire::test(CreateGolongan::class)
        ->fillForm(['jenis' => 'pppk', 'kode' => 'X', 'urutan' => 10])
        ->call('create')
        ->assertHasNoFormErrors();

    Livewire::test(CreateGolongan::class)
        ->fillForm(['jenis' => 'pns', 'kode' => 'X', 'urutan' => 11])
        ->call('create')
        ->assertHasFormErrors(['kode' => 'unique']);
});

test('admin kepegawaian dapat menambah status baru tanpa mengubah kode', function () {
    $this->actingAs(pegawaiAdmin(Peran::AdminKepegawaian));

    Livewire::test(CreateStatusKepegawaian::class)
        ->fillForm(['kode' => 'dosen-tamu', 'nama' => 'Dosen Tamu', 'kelompok' => 'non_asn', 'urutan' => 9])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(StatusKepegawaian::where('kode', 'dosen-tamu')->exists())->toBeTrue();
});

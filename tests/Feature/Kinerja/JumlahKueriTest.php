<?php

use App\Actions\Api\BuatTokenKlienApi;
use App\Enums\Peran;
use App\Filament\Admin\Pages\Dasbor;
use App\Filament\Admin\Resources\Pegawai\Pages\ListPegawai;
use App\Models\Pegawai;
use App\Models\Prodi;
use App\Models\RiwayatPendidikan;
use App\Models\Sertifikasi;
use App\Models\User;
use Database\Seeders\KonfigurasiSeeder;
use Database\Seeders\PeranDanIzinSeeder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(PeranDanIzinSeeder::class);
    $this->seed(KonfigurasiSeeder::class);
    Cache::flush();
    $this->admin = User::factory()->create(['email' => 'adm@unsil.ac.id', 'app_authentication_secret' => 'ABCDEFGHIJKLMNOP'])->assignRole(Peran::AdminKepegawaian->value);
    $prodi = Prodi::factory()->create();
    Pegawai::factory()->count(50)->create(['prodi_id' => $prodi->id])->each(function (Pegawai $p): void {
        RiwayatPendidikan::factory()->create(['pegawai_id' => $p->id, 'is_pendidikan_tertinggi' => true]);
        Sertifikasi::factory()->create(['pegawai_id' => $p->id]);
    });
});

/** @return array{0: int, 1: list<string>} */
function hitungKueri(Closure $aksi): array
{
    $kueri = [];
    DB::listen(function ($q) use (&$kueri): void {
        $kueri[] = $q->sql;
    });
    $aksi();

    return [count($kueri), $kueri];
}

test('daftar pegawai 50 baris memakai paling banyak 15 kueri', function () {
    $this->actingAs($this->admin);
    $komponen = Livewire::test(ListPegawai::class)->set('tableRecordsPerPage', 50);

    [$jumlah, $kueri] = hitungKueri(fn () => $komponen->loadTable());

    expect($jumlah)->toBeLessThanOrEqual(15, implode("\n", $kueri));
});

test('api dosen 50 item memakai paling banyak 10 kueri', function () {
    [, $polos] = app(BuatTokenKlienApi::class)->handle(User::factory()->create(['email' => 'sa@unsil.ac.id'])->assignRole(Peran::SuperAdmin->value), 'K', 'k');

    [$jumlah, $kueri] = hitungKueri(fn () => $this->withToken($polos)->getJson('/api/v1/dosen')->assertOk()->assertJsonCount(50, 'data'));

    expect($jumlah)->toBeLessThanOrEqual(10, implode("\n", $kueri));
});

test('dasbor dari cache memakai paling banyak 5 kueri statistik', function () {
    $this->actingAs($this->admin);
    Livewire::test(Dasbor::class); // memanaskan cache

    [$jumlah, $kueri] = hitungKueri(function () {
        $this->actingAs($this->admin);
        Livewire::test(Dasbor::class);
    });

    expect($jumlah)->toBeLessThanOrEqual(5, implode("\n", $kueri));
});

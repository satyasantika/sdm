<?php

use App\Actions\Laporan\HitungStatistikDasbor;
use App\Enums\Peran;
use App\Filament\Admin\Pages\Dasbor;
use App\Filament\Admin\Pages\LaporanAkreditasi;
use App\Filament\Admin\Resources\Pegawai\Pages\ListPegawai;
use App\Filament\Admin\Resources\Pegawai\Pages\ViewPegawai;
use App\Filament\Exports\ProfilDosenProdiExporter;
use App\Models\Pegawai;
use App\Models\User;
use Database\Seeders\BebanUjiSeeder;
use Database\Seeders\KonfigurasiSeeder;
use Database\Seeders\PeranDanIzinSeeder;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;

/**
 * Pengukuran kinerja (bukan bagian suite biasa): APP_UKUR_KINERJA=1 ./vendor/bin/pest tests/Feature/Kinerja/UkurKinerjaTest.php
 * Hasil dicatat di docs/KINERJA.md.
 */
function ukur(string $nama, Closure $aksi): float
{
    $mulai = hrtime(true);
    $aksi();
    $detik = (hrtime(true) - $mulai) / 1e9;
    fwrite(STDERR, sprintf("UKUR %-38s %.3f dtk\n", $nama, $detik));

    return $detik;
}

test('ukur kinerja dengan 1.000 pegawai', function () {
    $this->seed(PeranDanIzinSeeder::class);
    $this->seed(KonfigurasiSeeder::class);
    ukur('seeding beban uji', fn () => $this->seed(BebanUjiSeeder::class));
    $admin = User::factory()->create(['email' => 'adm@unsil.ac.id', 'app_authentication_secret' => 'ABCDEFGHIJKLMNOP'])->assignRole(Peran::AdminKepegawaian->value);
    $pegawai = Pegawai::query()->first();
    Cache::flush();

    $this->actingAs($admin);
    $daftar = ukur('daftar pegawai (50 baris)', fn () => Livewire::test(ListPegawai::class)->loadTable());
    $this->actingAs($admin);
    ukur('view pegawai', fn () => Livewire::test(ViewPegawai::class, ['record' => $pegawai->getKey()]));
    ukur('statistik dasbor (dingin)', fn () => app(HitungStatistikDasbor::class)->handle(null));
    ukur('statistik dasbor (cache)', fn () => app(HitungStatistikDasbor::class)->handle(null));
    $this->actingAs($admin);
    ukur('halaman dasbor', fn () => Livewire::test(Dasbor::class));

    $prodiId = $pegawai->prodi_id;
    $kolom = collect(ProfilDosenProdiExporter::getColumns())->mapWithKeys(fn ($k) => [$k->getName() => ['isEnabled' => true, 'label' => $k->getName()]])->all();
    $this->actingAs($admin);
    ukur('ekspor profil dosen prodi', fn () => Livewire::test(LaporanAkreditasi::class)->set('data.prodi_id', $prodiId)->callAction('eksporProfil', ['columnMap' => $kolom]));

    expect($daftar)->toBeLessThan(2.0);
})->skip(fn () => ! getenv('APP_UKUR_KINERJA'), 'Hanya dijalankan dengan APP_UKUR_KINERJA=1');

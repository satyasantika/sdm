<?php

use App\Enums\Peran;
use App\Filament\Admin\Resources\Pegawai\Pages\ListPegawai;
use App\Filament\Admin\Resources\Pegawai\Pages\ViewPegawai;
use App\Filament\Admin\Resources\UsulanPerubahan\Pages\ViewUsulanPerubahan;
use App\Filament\Swalayan\Pages\PersetujuanPrivasi;
use App\Models\Pegawai;
use App\Models\User;
use App\Models\UsulanPerubahan;
use App\Support\Konfigurasi;
use Database\Seeders\KonfigurasiSeeder;
use Database\Seeders\PeranDanIzinSeeder;
use Livewire\Livewire;

const XSS = '<script>alert(1)</script>';

beforeEach(function () {
    $this->seed(PeranDanIzinSeeder::class);
    $this->seed(KonfigurasiSeeder::class);
    $this->admin = User::factory()->create(['email' => 'adm@unsil.ac.id', 'app_authentication_secret' => 'ABCDEFGHIJKLMNOP'])->assignRole(Peran::AdminKepegawaian->value);
});

test('nama pegawai berisi script di-escape pada tabel dan halaman view', function () {
    $pegawai = Pegawai::factory()->create(['nama' => 'Budi '.XSS, 'gelar_belakang' => null]);
    $this->actingAs($this->admin);

    Livewire::test(ListPegawai::class)->loadTable()->assertDontSeeHtml(XSS);
    Livewire::test(ViewPegawai::class, ['record' => $pegawai->getKey()])->assertDontSeeHtml(XSS);
    $this->get('/admin/pegawai/pegawais')->assertOk()->assertDontSee(XSS, false);
});

test('catatan usulan berisi script di-escape pada halaman view', function () {
    $usulan = UsulanPerubahan::factory()->create(['status' => 'diajukan', 'alasan' => XSS, 'catatan_verifikator' => XSS]);
    $this->actingAs($this->admin);

    Livewire::test(ViewUsulanPerubahan::class, ['record' => $usulan->getKey()])->assertDontSeeHtml(XSS);
});

test('teks kebijakan privasi markdown membuang html mentah dan tautan berbahaya', function () {
    Konfigurasi::set('teks_kebijakan_privasi', 'Halo <script>alert(1)</script> **tebal** [klik](javascript:alert(1)) <img src=x onerror=alert(1)>');
    $dosen = User::factory()->create(['email' => 'dsn@unsil.ac.id'])->assignRole(Peran::Dosen->value);
    Pegawai::factory()->create(['user_id' => $dosen->id]);
    $this->actingAs($dosen);

    $html = Livewire::test(PersetujuanPrivasi::class)->instance()->teksHtml()->toHtml();

    expect($html)->toContain('<strong>tebal</strong>')->not->toContain('<script')->not->toContain('onerror')->not->toContain('javascript:');
});

test('tidak ada keluaran blade tanpa escape untuk data pengguna di kode aplikasi', function () {
    $pelanggar = [];
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(resource_path('views')));
    foreach ($iterator as $berkas) {
        if (str_ends_with((string) $berkas, '.blade.php') && str_contains(file_get_contents((string) $berkas), '{!!')) {
            $pelanggar[] = str_replace(base_path().'/', '', (string) $berkas);
        }
    }

    expect($pelanggar)->toBe([]);
});

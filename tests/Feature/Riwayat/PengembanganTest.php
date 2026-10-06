<?php

use App\Actions\Riwayat\SimpanPelatihan;
use App\Actions\Riwayat\SimpanPenghargaan;
use App\Enums\Peran;
use App\Enums\TingkatKegiatan;
use App\Filament\Admin\Resources\Pegawai\Pages\EditPegawai;
use App\Filament\Admin\Resources\Pegawai\RelationManagers\PelatihanRelationManager;
use App\Models\Pegawai;
use App\Models\Pelatihan;
use App\Models\Penghargaan;
use App\Models\Prodi;
use App\Models\User;
use Database\Seeders\PeranDanIzinSeeder;
use Filament\Actions\Testing\TestAction;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

beforeEach(fn () => $this->seed(PeranDanIzinSeeder::class));

function simpanLatih(Pegawai $pegawai, array $tambahan = []): Pelatihan
{
    return app(SimpanPelatihan::class)->handle($pegawai, array_merge([
        'nama' => 'Workshop Uji', 'jenis' => 'workshop', 'tanggal_mulai' => '2025-03-01', 'jumlah_jam' => 8,
    ], $tambahan));
}

test('penghargaan nasional tersimpan dengan enum', function () {
    $pegawai = Pegawai::factory()->create();

    $p = app(SimpanPenghargaan::class)->handle($pegawai, ['nama' => 'Satyalancana', 'tingkat' => 'nasional', 'kategori' => 'penghargaan']);

    expect($p->fresh()->tingkat)->toBe(TingkatKegiatan::Nasional)->and(Penghargaan::count())->toBe(1);
});

test('pelatihan dengan tanggal terbalik atau jam di luar rentang ditolak', function () {
    $pegawai = Pegawai::factory()->create();

    expect(fn () => simpanLatih($pegawai, ['tanggal_mulai' => '2025-05-02', 'tanggal_selesai' => '2025-05-01']))->toThrow(ValidationException::class)
        ->and(fn () => simpanLatih($pegawai, ['jumlah_jam' => 0]))->toThrow(ValidationException::class)
        ->and(fn () => simpanLatih($pegawai, ['jumlah_jam' => 2001]))->toThrow(ValidationException::class);
});

test('ringkasan total jam per tahun benar', function () {
    $pegawai = Pegawai::factory()->create();
    simpanLatih($pegawai, ['tanggal_mulai' => '2025-01-10', 'jumlah_jam' => 8]);
    simpanLatih($pegawai, ['tanggal_mulai' => '2025-06-10', 'jumlah_jam' => 16]);
    simpanLatih($pegawai, ['tanggal_mulai' => '2024-02-10', 'jumlah_jam' => 4]);
    simpanLatih($pegawai, ['tanggal_mulai' => '2024-03-10', 'jumlah_jam' => null]);

    expect($pegawai->jamPelatihanPerTahun())->toBe([2025 => 24, 2024 => 4]);
});

test('admin-prodi prodi lain ditolak dan prodi sendiri diizinkan', function () {
    $pmat = Prodi::factory()->create();
    $pbio = Prodi::factory()->create();
    $admin = tap(User::factory()->create(['email' => 'ap@unsil.ac.id', 'prodi_id' => $pmat->id]), fn ($u) => $u->assignRole(Peran::AdminProdi->value));
    $milikSendiri = Pelatihan::factory()->create(['pegawai_id' => Pegawai::factory()->create(['prodi_id' => $pmat->id])->id]);
    $milikLain = Pelatihan::factory()->create(['pegawai_id' => Pegawai::factory()->create(['prodi_id' => $pbio->id])->id]);
    $penghargaanLain = Penghargaan::factory()->create(['pegawai_id' => Pegawai::factory()->create(['prodi_id' => $pbio->id])->id]);

    expect($admin->can('update', $milikSendiri))->toBeTrue()
        ->and($admin->can('update', $milikLain))->toBeFalse()
        ->and($admin->can('view', $milikLain))->toBeFalse()
        ->and($admin->can('update', $penghargaanLain))->toBeFalse();
});

test('relation manager pelatihan menampilkan ringkasan dan menyimpan', function () {
    $pegawai = Pegawai::factory()->create();
    simpanLatih($pegawai, ['tanggal_mulai' => '2025-01-10', 'jumlah_jam' => 8]);
    $admin = tap(User::factory()->create(['email' => 'ak@unsil.ac.id', 'app_authentication_secret' => 'ABCDEFGHIJKLMNOP']), fn ($u) => $u->assignRole(Peran::AdminKepegawaian->value));
    $this->actingAs($admin);

    Livewire::test(PelatihanRelationManager::class, ['ownerRecord' => $pegawai, 'pageClass' => EditPegawai::class])
        ->assertSee('2025: 8 jam')
        ->callAction(TestAction::make('create')->table(), ['nama' => 'Diklat Baru', 'jenis' => 'diklat_fungsional', 'tanggal_mulai' => '2025-09-01', 'jumlah_jam' => 40])
        ->assertHasNoFormErrors();

    expect($pegawai->jamPelatihanPerTahun())->toBe([2025 => 48]);
});

<?php

use App\Actions\Riwayat\SimpanStudiLanjut;
use App\Enums\Peran;
use App\Enums\StatusAktifPegawai;
use App\Filament\Admin\Resources\Pegawai\Pages\EditPegawai;
use App\Filament\Admin\Resources\Pegawai\RelationManagers\StudiLanjutRelationManager;
use App\Models\JenjangPendidikan;
use App\Models\Pegawai;
use App\Models\RiwayatStatusPegawai;
use App\Models\User;
use Database\Seeders\MasterPendukungSeeder;
use Database\Seeders\PeranDanIzinSeeder;
use Filament\Actions\Testing\TestAction;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(PeranDanIzinSeeder::class);
    $this->seed(MasterPendukungSeeder::class);
});

function pelaku(Peran $peran): User
{
    return tap(User::factory()->create([
        'email' => $peran->value.fake()->unique()->numerify('###').'@unsil.ac.id',
        'app_authentication_secret' => 'ABCDEFGHIJKLMNOP',
    ]), fn (User $u) => $u->assignRole($peran->value));
}

function dataStudi(array $tambahan = []): array
{
    return array_merge([
        'jenis' => 'tugas_belajar', 'jenjang_pendidikan_id' => JenjangPendidikan::firstWhere('kode', 'S3')->id,
        'nama_pt' => 'Universitas Contoh', 'tanggal_mulai' => '2025-09-01', 'status' => 'berjalan', 'nomor_sk' => 'SK/TB/1',
    ], $tambahan);
}

test('tugas belajar berjalan mengubah status pegawai dan mencatat riwayat status', function () {
    $pegawai = Pegawai::factory()->create();

    app(SimpanStudiLanjut::class)->handle($pegawai, dataStudi(), pelaku(Peran::AdminKepegawaian));

    $riwayat = RiwayatStatusPegawai::where('pegawai_id', $pegawai->id)->first();
    expect($pegawai->fresh()->status_aktif)->toBe(StatusAktifPegawai::TugasBelajar)
        ->and($riwayat->ke_status)->toBe(StatusAktifPegawai::TugasBelajar)
        ->and($riwayat->tmt->toDateString())->toBe('2025-09-01')
        ->and($riwayat->nomor_sk)->toBe('SK/TB/1');
});

test('status selesai mengembalikan pegawai ke aktif', function () {
    $pegawai = Pegawai::factory()->create();
    $admin = pelaku(Peran::AdminKepegawaian);
    $studi = app(SimpanStudiLanjut::class)->handle($pegawai, dataStudi(), $admin);

    app(SimpanStudiLanjut::class)->handle($pegawai, dataStudi(['status' => 'selesai', 'tanggal_selesai_aktual' => '2026-08-31']), $admin, $studi);

    expect($pegawai->fresh()->status_aktif)->toBe(StatusAktifPegawai::Aktif)
        ->and(RiwayatStatusPegawai::where('pegawai_id', $pegawai->id)->count())->toBe(2)
        ->and(RiwayatStatusPegawai::where('pegawai_id', $pegawai->id)->latest('tmt')->first()->tmt->toDateString())->toBe('2026-08-31');
});

test('izin belajar tidak mengubah status pegawai', function () {
    $pegawai = Pegawai::factory()->create();

    app(SimpanStudiLanjut::class)->handle($pegawai, dataStudi(['jenis' => 'izin_belajar']), pelaku(Peran::AdminKepegawaian));

    expect($pegawai->fresh()->status_aktif)->toBe(StatusAktifPegawai::Aktif)->and(RiwayatStatusPegawai::count())->toBe(0);
});

test('tanpa izin ubah pegawai atau dengan sinkron dimatikan status tidak berubah', function () {
    $pegawai = Pegawai::factory()->create();
    $tanpaIzin = pelaku(Peran::Pimpinan);

    app(SimpanStudiLanjut::class)->handle($pegawai, dataStudi(), $tanpaIzin);
    expect($pegawai->fresh()->status_aktif)->toBe(StatusAktifPegawai::Aktif);

    $pegawai2 = Pegawai::factory()->create();
    app(SimpanStudiLanjut::class)->handle($pegawai2, dataStudi(), pelaku(Peran::AdminKepegawaian), null, false);
    expect($pegawai2->fresh()->status_aktif)->toBe(StatusAktifPegawai::Aktif);
});

test('tanggal selesai sebelum mulai ditolak', function () {
    expect(fn () => app(SimpanStudiLanjut::class)->handle(Pegawai::factory()->create(), dataStudi(['tanggal_selesai_rencana' => '2025-01-01']), pelaku(Peran::AdminKepegawaian)))
        ->toThrow(ValidationException::class);
});

test('relation manager menyimpan studi lanjut dan menyinkron status', function () {
    $pegawai = Pegawai::factory()->create();
    $this->actingAs(pelaku(Peran::AdminKepegawaian));

    Livewire::test(StudiLanjutRelationManager::class, ['ownerRecord' => $pegawai, 'pageClass' => EditPegawai::class])
        ->callAction(TestAction::make('create')->table(), dataStudi(['sinkron_status' => true]))
        ->assertHasNoFormErrors();

    expect($pegawai->fresh()->status_aktif)->toBe(StatusAktifPegawai::TugasBelajar);
});

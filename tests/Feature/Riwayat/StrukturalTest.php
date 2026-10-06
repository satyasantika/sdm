<?php

use App\Actions\Riwayat\SimpanRiwayatJabatanStruktural;
use App\Enums\Peran;
use App\Filament\Admin\Resources\Pegawai\Pages\EditPegawai;
use App\Filament\Admin\Resources\Pegawai\RelationManagers\StrukturalRelationManager;
use App\Filament\Admin\Widgets\PejabatAktifWidget;
use App\Models\JenisJabatanStruktural;
use App\Models\Pegawai;
use App\Models\Prodi;
use App\Models\RiwayatJabatanStruktural;
use App\Models\UnitKerja;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\PeranDanIzinSeeder;
use Filament\Actions\Testing\TestAction;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

beforeEach(fn () => $this->seed(PeranDanIzinSeeder::class));

function aktorStruktural(Peran $peran, ?Prodi $prodi = null): User
{
    return tap(User::factory()->create([
        'email' => $peran->value.fake()->unique()->numerify('###').'@unsil.ac.id',
        'app_authentication_secret' => 'ABCDEFGHIJKLMNOP',
        'prodi_id' => $prodi?->id,
    ]), fn (User $u) => $u->assignRole($peran->value));
}

function simpanStruktural(Pegawai $pegawai, JenisJabatanStruktural $jabatan, ?UnitKerja $unit, string $mulai, ?string $selesai = null): RiwayatJabatanStruktural
{
    return app(SimpanRiwayatJabatanStruktural::class)->handle($pegawai, [
        'jenis_jabatan_struktural_id' => $jabatan->id, 'unit_kerja_id' => $unit?->id,
        'tmt_mulai' => $mulai, 'tmt_selesai' => $selesai, 'nomor_sk' => 'SK/'.$mulai,
    ]);
}

test('tmt selesai sebelum mulai ditolak', function () {
    expect(fn () => simpanStruktural(Pegawai::factory()->create(), JenisJabatanStruktural::factory()->create(), null, '2024-01-01', '2023-12-31'))
        ->toThrow(ValidationException::class);
});

test('periode tumpang tindih untuk pegawai dan jabatan serta unit yang sama ditolak', function () {
    $pegawai = Pegawai::factory()->create();
    $jabatan = JenisJabatanStruktural::factory()->create();
    $unit = UnitKerja::factory()->create();
    simpanStruktural($pegawai, $jabatan, $unit, '2022-01-01', '2024-12-31');

    expect(fn () => simpanStruktural($pegawai, $jabatan, $unit, '2024-06-01'))->toThrow(ValidationException::class);

    simpanStruktural($pegawai, $jabatan, $unit, '2025-01-01');
    expect(RiwayatJabatanStruktural::where('pegawai_id', $pegawai->id)->count())->toBe(2);
});

test('jabatan atau unit berbeda tidak dianggap tumpang tindih', function () {
    $pegawai = Pegawai::factory()->create();
    $jabatan = JenisJabatanStruktural::factory()->create();
    simpanStruktural($pegawai, $jabatan, UnitKerja::factory()->create(), '2022-01-01');
    simpanStruktural($pegawai, $jabatan, UnitKerja::factory()->create(), '2022-06-01');
    simpanStruktural($pegawai, JenisJabatanStruktural::factory()->create(), null, '2022-06-01');

    expect(RiwayatJabatanStruktural::count())->toBe(3);
});

test('scope aktif benar untuk null dan tanggal lampau', function () {
    Carbon::setTestNow('2026-10-06');
    $aktifTanpaSelesai = RiwayatJabatanStruktural::factory()->create(['tmt_selesai' => null]);
    $aktifHariIni = RiwayatJabatanStruktural::factory()->create(['tmt_selesai' => '2026-10-06']);
    $lampau = RiwayatJabatanStruktural::factory()->create(['tmt_selesai' => '2025-01-01']);

    $aktif = RiwayatJabatanStruktural::aktif()->pluck('id');

    expect($aktif)->toContain($aktifTanpaSelesai->id, $aktifHariIni->id)->not->toContain($lampau->id)
        ->and($lampau->isAktif())->toBeFalse();
    Carbon::setTestNow();
});

test('periode berformat indonesia dan sekarang bila belum selesai', function () {
    $r = RiwayatJabatanStruktural::factory()->create(['tmt_mulai' => '2023-03-01', 'tmt_selesai' => null]);

    expect($r->periode)->toBe('1 Maret 2023 – sekarang');
    $r->update(['tmt_selesai' => '2024-08-17']);
    expect($r->fresh()->periode)->toBe('1 Maret 2023 – 17 Agustus 2024');
});

test('pemegang lain pada periode sama dikembalikan sebagai peringatan', function () {
    $jabatan = JenisJabatanStruktural::factory()->create();
    $unit = UnitKerja::factory()->create();
    $pertama = Pegawai::factory()->create(['nama' => 'Pemegang Pertama', 'gelar_belakang' => null]);
    $kedua = Pegawai::factory()->create();
    simpanStruktural($pertama, $jabatan, $unit, '2024-01-01');
    $baru = simpanStruktural($kedua, $jabatan, $unit, '2024-06-01');

    expect(app(SimpanRiwayatJabatanStruktural::class)->pemegangLain($baru))->toBe(['Pemegang Pertama']);
});

test('admin-prodi hanya baca dan admin-kepegawaian dapat menulis', function () {
    $prodi = Prodi::factory()->create();

    expect(aktorStruktural(Peran::AdminProdi, $prodi)->can('create', RiwayatJabatanStruktural::class))->toBeFalse()
        ->and(aktorStruktural(Peran::AdminProdi, $prodi)->can('viewAny', RiwayatJabatanStruktural::class))->toBeTrue()
        ->and(aktorStruktural(Peran::AdminKepegawaian)->can('create', RiwayatJabatanStruktural::class))->toBeTrue();
});

test('relation manager menyimpan dan widget menampilkan pejabat aktif', function () {
    $pegawai = Pegawai::factory()->create(['nama' => 'Dekan Contoh']);
    $jabatan = JenisJabatanStruktural::factory()->create(['nama' => 'Dekan']);
    $this->actingAs(aktorStruktural(Peran::AdminKepegawaian));

    Livewire::test(StrukturalRelationManager::class, ['ownerRecord' => $pegawai, 'pageClass' => EditPegawai::class])
        ->callAction(TestAction::make('create')->table(), [
            'jenis_jabatan_struktural_id' => $jabatan->id, 'tmt_mulai' => '2024-01-01', 'nomor_sk' => 'SK/Dekan',
        ])->assertHasNoFormErrors();

    $riwayat = RiwayatJabatanStruktural::first();
    Livewire::test(PejabatAktifWidget::class)->assertCanSeeTableRecords([$riwayat]);
});

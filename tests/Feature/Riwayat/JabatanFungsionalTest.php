<?php

use App\Actions\Riwayat\HapusRiwayatJabatanFungsional;
use App\Actions\Riwayat\SimpanRiwayatJabatanFungsional;
use App\Enums\Peran;
use App\Filament\Admin\Resources\Pegawai\Pages\EditPegawai;
use App\Filament\Admin\Resources\Pegawai\PegawaiResource;
use App\Filament\Admin\Resources\Pegawai\RelationManagers\JabatanFungsionalRelationManager;
use App\Models\JabatanFungsional;
use App\Models\Pegawai;
use App\Models\Prodi;
use App\Models\RiwayatJabatanFungsional;
use App\Models\User;
use Database\Seeders\KonfigurasiSeeder;
use Database\Seeders\MasterJabatanSeeder;
use Database\Seeders\PeranDanIzinSeeder;
use Filament\Actions\Testing\TestAction;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(PeranDanIzinSeeder::class);
    $this->seed(KonfigurasiSeeder::class);
    $this->seed(MasterJabatanSeeder::class);
});

function jabatan(string $kode): JabatanFungsional
{
    return JabatanFungsional::firstWhere('kode', $kode);
}

function aktorJabfung(Peran $peran, ?Prodi $prodi = null): User
{
    return tap(User::factory()->create([
        'email' => $peran->value.fake()->unique()->numerify('###').'@unsil.ac.id',
        'app_authentication_secret' => 'ABCDEFGHIJKLMNOP',
        'prodi_id' => $prodi?->id,
    ]), fn (User $u) => $u->assignRole($peran->value));
}

function tambahJabfung(Pegawai $pegawai, string $kode, string $tmt, ?User $oleh = null, array $tambahan = []): RiwayatJabatanFungsional
{
    return app(SimpanRiwayatJabatanFungsional::class)->handle($pegawai, array_merge([
        'jabatan_fungsional_id' => jabatan($kode)->id, 'tmt' => $tmt, 'nomor_sk' => 'SK/'.$kode.'/'.$tmt,
    ], $tambahan), $oleh ?? aktorJabfung(Peran::AdminKepegawaian));
}

test('jabatan terkini mengikuti tmt terbaru dan hanya satu is_terkini', function () {
    $pegawai = Pegawai::factory()->create();

    tambahJabfung($pegawai, 'lektor', '2022-03-01');
    tambahJabfung($pegawai, 'lektor-kepala', '2026-03-01');

    expect($pegawai->fresh()->jabatan_fungsional_id)->toBe(jabatan('lektor-kepala')->id)
        ->and(RiwayatJabatanFungsional::where('pegawai_id', $pegawai->id)->where('is_terkini', true)->count())->toBe(1)
        ->and($pegawai->jabatanFungsionalTerkini->jabatan_fungsional_id)->toBe(jabatan('lektor-kepala')->id);
});

test('riwayat lama tidak mengubah jabatan terkini', function () {
    $pegawai = Pegawai::factory()->create();
    tambahJabfung($pegawai, 'lektor', '2022-03-01');

    tambahJabfung($pegawai, 'asisten-ahli', '2018-03-01');

    expect($pegawai->fresh()->jabatan_fungsional_id)->toBe(jabatan('lektor')->id)
        ->and(RiwayatJabatanFungsional::where('pegawai_id', $pegawai->id)->count())->toBe(2);
});

test('turun jenjang dengan tmt lebih baru ditolak kecuali koreksi admin kepegawaian', function () {
    $pegawai = Pegawai::factory()->create();
    tambahJabfung($pegawai, 'lektor-kepala', '2022-03-01');

    expect(fn () => tambahJabfung($pegawai, 'lektor', '2026-03-01'))->toThrow(ValidationException::class);

    tambahJabfung($pegawai, 'lektor', '2026-03-01', null, ['is_koreksi' => true]);
    expect($pegawai->fresh()->jabatan_fungsional_id)->toBe(jabatan('lektor')->id);
});

test('koreksi ditolak bila bukan admin kepegawaian', function () {
    $prodi = Prodi::factory()->create();
    $pegawai = Pegawai::factory()->create(['prodi_id' => $prodi->id]);
    tambahJabfung($pegawai, 'lektor-kepala', '2022-03-01');

    expect(fn () => tambahJabfung($pegawai, 'lektor', '2026-03-01', aktorJabfung(Peran::AdminProdi, $prodi), ['is_koreksi' => true]))
        ->toThrow(ValidationException::class);
});

test('menetapkan profesor mengubah tanggal pensiun ke bup 70', function () {
    $pegawai = Pegawai::factory()->create(['tanggal_lahir' => '1965-03-15']);
    expect($pegawai->fresh()->tanggal_pensiun->toDateString())->toBe('2030-04-01');

    tambahJabfung($pegawai, 'profesor', '2026-03-01');

    expect($pegawai->fresh()->tanggal_pensiun->toDateString())->toBe('2035-04-01');
});

test('jabatan tendik tidak dapat dipasang pada dosen', function () {
    $pegawai = Pegawai::factory()->create();
    $jabatanTendik = JabatanFungsional::factory()->create(['kelompok' => 'tendik', 'rumpun' => 'Pustakawan', 'urutan' => 1]);

    expect(fn () => app(SimpanRiwayatJabatanFungsional::class)->handle($pegawai, [
        'jabatan_fungsional_id' => $jabatanTendik->id, 'tmt' => '2026-01-01', 'nomor_sk' => 'SK/1',
    ], aktorJabfung(Peran::AdminKepegawaian)))->toThrow(ValidationException::class);
});

test('menghapus riwayat terkini tanpa pengganti ditolak, dengan pengganti menyinkron ulang', function () {
    $pegawai = Pegawai::factory()->create();
    $lama = tambahJabfung($pegawai, 'asisten-ahli', '2018-03-01');

    expect(fn () => app(HapusRiwayatJabatanFungsional::class)->handle($lama->fresh()))->toThrow(ValidationException::class);

    $baru = tambahJabfung($pegawai, 'lektor', '2022-03-01');
    app(HapusRiwayatJabatanFungsional::class)->handle($baru->fresh());

    expect($pegawai->fresh()->jabatan_fungsional_id)->toBe(jabatan('asisten-ahli')->id)
        ->and($lama->fresh()->is_terkini)->toBeTrue();
});

test('admin-prodi menambah riwayat dosen prodinya dan tidak dosen prodi lain', function () {
    $pmat = Prodi::factory()->create();
    $pbio = Prodi::factory()->create();
    $dosenPmat = Pegawai::factory()->create(['prodi_id' => $pmat->id]);
    $dosenPbio = Pegawai::factory()->create(['prodi_id' => $pbio->id]);
    $adminPmat = aktorJabfung(Peran::AdminProdi, $pmat);

    expect($adminPmat->can('create', RiwayatJabatanFungsional::class))->toBeTrue()
        ->and($adminPmat->can('update', RiwayatJabatanFungsional::factory()->create(['pegawai_id' => $dosenPmat->id])))->toBeTrue()
        ->and($adminPmat->can('update', RiwayatJabatanFungsional::factory()->create(['pegawai_id' => $dosenPbio->id])))->toBeFalse()
        ->and($adminPmat->can('delete', RiwayatJabatanFungsional::factory()->create(['pegawai_id' => $dosenPmat->id])))->toBeFalse();
});

test('relation manager menambah riwayat lewat form dan menyinkron jabatan pegawai', function () {
    $pegawai = Pegawai::factory()->create();
    $this->actingAs(aktorJabfung(Peran::AdminKepegawaian));

    Livewire::test(JabatanFungsionalRelationManager::class, ['ownerRecord' => $pegawai, 'pageClass' => EditPegawai::class])
        ->callAction(TestAction::make('create')->table(), [
            'jabatan_fungsional_id' => jabatan('lektor')->id, 'tmt' => '2024-01-01', 'nomor_sk' => 'SK/2024',
            'tautan_sk' => 'https://drive.google.com/file/d/1AbCdEfGhIjKlMnOpQrStUvWxYz012345/view',
            'tautan_sk_konfirmasi' => true,
        ])
        ->assertHasNoFormErrors();

    $riwayat = RiwayatJabatanFungsional::where('pegawai_id', $pegawai->id)->first();
    expect($pegawai->fresh()->jabatan_fungsional_id)->toBe(jabatan('lektor')->id)
        ->and($riwayat->tautan('sk')->url)->toContain('1AbCdEfGhIjKl')
        ->and($riwayat->tautan('sk')->is_sensitif)->toBeTrue();
});

test('tab jabatan fungsional muncul di halaman pegawai', function () {
    $pegawai = Pegawai::factory()->create();
    tambahJabfung($pegawai, 'lektor', '2022-03-01');
    $this->actingAs(aktorJabfung(Peran::AdminKepegawaian));

    $this->get(PegawaiResource::getUrl('view', ['record' => $pegawai]))
        ->assertOk()->assertSee('Jabatan Fungsional');
});

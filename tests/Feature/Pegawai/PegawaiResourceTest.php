<?php

use App\Enums\Peran;
use App\Filament\Admin\Resources\Pegawai\Pages\CreatePegawai;
use App\Filament\Admin\Resources\Pegawai\Pages\EditPegawai;
use App\Filament\Admin\Resources\Pegawai\Pages\ListPegawai;
use App\Filament\Admin\Resources\Pegawai\Pages\ViewPegawai;
use App\Filament\Admin\Resources\Pegawai\PegawaiResource;
use App\Models\Pegawai;
use App\Models\Prodi;
use App\Models\StatusKepegawaian;
use App\Models\UnitKerja;
use App\Models\User;
use Database\Seeders\KonfigurasiSeeder;
use Database\Seeders\PeranDanIzinSeeder;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(PeranDanIzinSeeder::class);
    $this->seed(KonfigurasiSeeder::class);
});

function adminDengan(Peran $peran, ?Prodi $prodi = null): User
{
    return tap(User::factory()->create([
        'email' => $peran->value.fake()->unique()->numerify('###').'@unsil.ac.id',
        'app_authentication_secret' => 'ABCDEFGHIJKLMNOP',
        'prodi_id' => $prodi?->id,
    ]), fn (User $u) => $u->assignRole($peran->value));
}

function dataDosenLengkap(Prodi $prodi, StatusKepegawaian $status, array $timpa = []): array
{
    return array_merge([
        'jenis_pegawai' => 'dosen',
        'nama' => 'Siti Aminah',
        'gelar_belakang' => 'M.Pd.',
        'nip' => '198501012010012001',
        'nidn' => '0401018501',
        'nuptk' => '1234567890123456',
        'nik' => '3278010101850001',
        'tanggal_lahir' => '1985-01-01',
        'jenis_kelamin' => 'P',
        'status_kepegawaian_id' => $status->id,
        'prodi_id' => $prodi->id,
        'email_unsil' => 'siti@unsil.ac.id',
        'npwp' => '12.345.678.9-012.345',
    ], $timpa);
}

test('admin-kepegawaian membuat dosen lengkap dengan nik terenkripsi', function () {
    $prodi = Prodi::factory()->create();
    $status = StatusKepegawaian::factory()->create();
    $this->actingAs(adminDengan(Peran::AdminKepegawaian));

    Livewire::test(CreatePegawai::class)
        ->fillForm(dataDosenLengkap($prodi, $status))
        ->call('create')
        ->assertHasNoFormErrors();

    $pegawai = Pegawai::firstWhere('nip', '198501012010012001');
    expect($pegawai->nik)->toBe('3278010101850001')
        ->and(DB::table('pegawai')->where('id', $pegawai->id)->value('nik'))->not->toBe('3278010101850001')
        ->and($pegawai->tanggal_pensiun)->not->toBeNull();
});

test('admin-prodi hanya melihat dosen prodinya', function () {
    $pmat = Prodi::factory()->create(['kode' => 'PMAT']);
    $pbio = Prodi::factory()->create(['kode' => 'PBIO']);
    $dosenPmat = Pegawai::factory()->create(['prodi_id' => $pmat->id]);
    $dosenPbio = Pegawai::factory()->create(['prodi_id' => $pbio->id]);
    $this->actingAs(adminDengan(Peran::AdminProdi, $pmat));

    Livewire::test(ListPegawai::class)
        ->loadTable()
        ->assertCanSeeTableRecords([$dosenPmat])
        ->assertCanNotSeeTableRecords([$dosenPbio]);
});

test('admin-prodi tidak dapat membuka edit dosen prodi lain (IDOR)', function () {
    $pmat = Prodi::factory()->create();
    $pbio = Prodi::factory()->create();
    $dosenPbio = Pegawai::factory()->create(['prodi_id' => $pbio->id]);
    $this->actingAs(adminDengan(Peran::AdminProdi, $pmat));

    $status = $this->get(PegawaiResource::getUrl('edit', ['record' => $dosenPbio]))->status();

    expect($status)->toBeIn([403, 404]);
});

test('admin-prodi tanpa prodi tidak melihat pegawai apa pun', function () {
    Pegawai::factory()->tendik()->create();
    Pegawai::factory()->create();
    $this->actingAs(adminDengan(Peran::AdminProdi));

    Livewire::test(ListPegawai::class)->loadTable()->assertCountTableRecords(0);
});

test('admin-prodi hanya dapat membuat dosen di prodinya sendiri', function () {
    $pmat = Prodi::factory()->create();
    $pbio = Prodi::factory()->create();
    $status = StatusKepegawaian::factory()->create();
    $this->actingAs(adminDengan(Peran::AdminProdi, $pmat));

    Livewire::test(CreatePegawai::class)
        ->fillForm(dataDosenLengkap($pbio, $status, ['jenis_pegawai' => 'tendik', 'unit_kerja_id' => UnitKerja::factory()->create()->id]))
        ->call('create')
        ->assertHasNoFormErrors();

    $pegawai = Pegawai::firstWhere('nip', '198501012010012001');
    expect($pegawai)->not->toBeNull()
        ->and($pegawai->prodi_id)->toBe($pmat->id)
        ->and($pegawai->jenis_pegawai->value)->toBe('dosen')
        ->and($pegawai->unit_kerja_id)->toBeNull();
});

test('dosen tanpa prodi dan tendik tanpa unit kerja ditolak validasi', function () {
    $status = StatusKepegawaian::factory()->create();
    $this->actingAs(adminDengan(Peran::AdminKepegawaian));

    Livewire::test(CreatePegawai::class)
        ->fillForm(['jenis_pegawai' => 'dosen', 'nama' => 'Tanpa Prodi', 'status_kepegawaian_id' => $status->id])
        ->call('create')
        ->assertHasFormErrors(['prodi_id' => 'required']);

    Livewire::test(CreatePegawai::class)
        ->fillForm(['jenis_pegawai' => 'tendik', 'nama' => 'Tanpa Unit', 'status_kepegawaian_id' => $status->id])
        ->call('create')
        ->assertHasFormErrors(['unit_kerja_id' => 'required']);
});

test('tendik dengan unit kerja dapat dibuat', function () {
    $status = StatusKepegawaian::factory()->create();
    $unit = UnitKerja::factory()->create();
    $this->actingAs(adminDengan(Peran::AdminKepegawaian));

    Livewire::test(CreatePegawai::class)
        ->fillForm(['jenis_pegawai' => 'tendik', 'nama' => 'Budi Tendik', 'status_kepegawaian_id' => $status->id, 'unit_kerja_id' => $unit->id])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Pegawai::firstWhere('nama', 'Budi Tendik')->unit_kerja_id)->toBe($unit->id);
});

test('nik yang sama dengan pegawai lain ditolak', function () {
    Pegawai::factory()->create(['nik' => '3278010101850001']);
    $prodi = Prodi::factory()->create();
    $status = StatusKepegawaian::factory()->create();
    $this->actingAs(adminDengan(Peran::AdminKepegawaian));

    Livewire::test(CreatePegawai::class)
        ->fillForm(dataDosenLengkap($prodi, $status))
        ->call('create')
        ->assertHasFormErrors(['nik']);
});

test('mengedit tanpa mengisi nik tidak menghapus nik lama dan form tidak menampilkannya', function () {
    $pegawai = Pegawai::factory()->create(['nik' => '3278010101850001', 'npwp' => '12.345.678.9-012.345']);
    $this->actingAs(adminDengan(Peran::AdminKepegawaian));

    Livewire::test(EditPegawai::class, ['record' => $pegawai->getKey()])
        ->assertFormSet(['nik' => null, 'npwp' => null])
        ->fillForm(['nama' => 'Nama Diperbarui'])
        ->call('save')
        ->assertHasNoFormErrors();

    $segar = $pegawai->fresh();
    expect($segar->nama)->toBe('Nama Diperbarui')
        ->and($segar->nik)->toBe('3278010101850001')
        ->and($segar->npwp)->toBe('12.345.678.9-012.345');
});

test('pimpinan dapat melihat tetapi tidak mengubah', function () {
    $pegawai = Pegawai::factory()->create();
    $pimpinan = adminDengan(Peran::Pimpinan);
    $this->actingAs($pimpinan);

    $this->get(PegawaiResource::getUrl('view', ['record' => $pegawai]))->assertOk();
    $this->get(PegawaiResource::getUrl('edit', ['record' => $pegawai]))->assertForbidden();
    $this->get(PegawaiResource::getUrl('create'))->assertForbidden();
});

test('hanya super-admin yang boleh menghapus pegawai', function () {
    $pegawai = Pegawai::factory()->create();

    expect(adminDengan(Peran::AdminKepegawaian)->can('delete', $pegawai))->toBeFalse()
        ->and(adminDengan(Peran::AdminKepegawaian)->can('forceDelete', $pegawai))->toBeFalse()
        ->and(adminDengan(Peran::SuperAdmin)->can('delete', $pegawai))->toBeTrue();
});

test('admin-kepegawaian dapat mengubah status keaktifan lewat aksi', function () {
    $pegawai = Pegawai::factory()->create();
    $this->actingAs(adminDengan(Peran::AdminKepegawaian));

    Livewire::test(ViewPegawai::class, ['record' => $pegawai->getKey()])
        ->callAction('ubahStatus', ['ke' => 'tugas_belajar', 'tmt' => '2026-09-01', 'nomor_sk' => 'SK/1'])
        ->assertHasNoActionErrors();

    expect($pegawai->fresh()->status_aktif->value)->toBe('tugas_belajar')
        ->and($pegawai->riwayatStatus()->count())->toBe(1);
});

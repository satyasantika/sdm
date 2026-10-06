<?php

use App\Actions\Pegawai\BuatAkunPegawai;
use App\Enums\Peran;
use App\Filament\Admin\Resources\Pegawai\Pages\ListPegawai;
use App\Filament\Imports\PegawaiImporter;
use App\Models\BarisImporGagal;
use App\Models\Pegawai;
use App\Models\Prodi;
use App\Models\StatusKepegawaian;
use App\Models\UnitKerja;
use App\Models\User;
use App\Notifications\AkunSwalayanDibuat;
use Database\Seeders\KonfigurasiSeeder;
use Database\Seeders\PeranDanIzinSeeder;
use Filament\Actions\ImportAction;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(PeranDanIzinSeeder::class);
    $this->seed(KonfigurasiSeeder::class);
    Prodi::factory()->create(['kode' => 'PMAT']);
    StatusKepegawaian::factory()->create(['kode' => 'pns']);
    UnitKerja::factory()->create(['kode' => 'SB-UMUM']);
});

function adminImpor(Peran $peran): User
{
    return tap(User::factory()->create([
        'email' => $peran->value.fake()->unique()->numerify('###').'@unsil.ac.id',
        'app_authentication_secret' => 'ABCDEFGHIJKLMNOP',
    ]), fn (User $u) => $u->assignRole($peran->value));
}

function barisDosen(array $timpa = []): array
{
    return array_merge([
        'jenis_pegawai' => 'dosen', 'nama' => 'Siti Aminah', 'nip' => '198501012010012001', 'nidn' => '0401018501',
        'nik' => '3278010101850001', 'tanggal_lahir' => '15/03/1985', 'kode_status_kepegawaian' => 'pns',
        'kode_prodi' => 'PMAT', 'email_unsil' => 'siti@unsil.ac.id',
    ], $timpa);
}

test('baris valid diimpor dan tanggal d/m/Y dinormalkan', function () {
    PegawaiImporter::test()->import(barisDosen())->assertImported();

    $pegawai = Pegawai::firstWhere('nip', '198501012010012001');
    expect($pegawai->tanggal_lahir->toDateString())->toBe('1985-03-15')
        ->and($pegawai->nik)->toBe('3278010101850001')
        ->and($pegawai->prodi->kode)->toBe('PMAT');
});

test('kode prodi tidak dikenal gagal dengan pesan indonesia', function () {
    $hasil = PegawaiImporter::test()->import(barisDosen(['kode_prodi' => 'XXXX']));

    expect($hasil->errors()->first('kode_prodi'))->toBe('Kode prodi tidak dikenal.');
});

test('nidn ganda gagal dan dosen tanpa prodi ditolak', function () {
    Pegawai::factory()->create(['nidn' => '0401018501']);

    $ganda = PegawaiImporter::test()->import(barisDosen(['nip' => '198501012010012999']));
    expect($ganda->errors()->first('nidn'))->toBe('NIDN sudah terdaftar.');

    PegawaiImporter::test()->import(barisDosen(['nidn' => '0401018599', 'kode_prodi' => null]))
        ->assertHasRowFailure('Dosen wajib memiliki kode prodi.');
});

test('impor ulang dengan nip sama memperbarui tanpa menggandakan dan tidak mengosongkan kolom', function () {
    PegawaiImporter::test()->import(barisDosen())->assertImported();
    PegawaiImporter::test()->import(barisDosen(['nama' => 'Siti Aminah Baru', 'nik' => null, 'no_hp' => '081111111111']))->assertImported();

    $pegawai = Pegawai::where('nip', '198501012010012001')->get();
    expect($pegawai)->toHaveCount(1)
        ->and($pegawai->first()->nama)->toBe('Siti Aminah Baru')
        ->and($pegawai->first()->no_hp)->toBe('081111111111')
        ->and($pegawai->first()->nik)->toBe('3278010101850001');
});

test('impor csv lewat aksi: 3 baris valid dan 1 nidn ganda menghasilkan 3 tersimpan dan 1 gagal', function () {
    Pegawai::factory()->create(['nidn' => '0409999999']);
    $this->actingAs(adminImpor(Peran::AdminKepegawaian));

    $kolom = ['jenis_pegawai', 'nama', 'nip', 'nidn', 'kode_status_kepegawaian', 'kode_prodi', 'kode_unit_kerja', 'email_unsil'];
    $baris = [
        ['dosen', 'Dosen Satu', '198001012005011001', '0401010001', 'pns', 'PMAT', '', 'satu@unsil.ac.id'],
        ['dosen', 'Dosen Dua', '198001012005011002', '0401010002', 'pns', 'PMAT', '', 'dua@unsil.ac.id'],
        ['tendik', 'Tendik Tiga', '198001012005011003', '', 'pns', '', 'SB-UMUM', 'tiga@unsil.ac.id'],
        ['dosen', 'Dosen Ganda', '198001012005011004', '0409999999', 'pns', 'PMAT', '', 'empat@unsil.ac.id'],
    ];
    $csv = implode("\n", [implode(',', $kolom), ...array_map(fn ($b) => implode(',', $b), $baris)]);

    Livewire::test(ListPegawai::class)
        ->callAction(ImportAction::class, [
            'file' => UploadedFile::fake()->createWithContent('pegawai.csv', $csv),
            'columnMap' => array_combine($kolom, $kolom),
        ])
        ->assertHasNoActionErrors();

    expect(Pegawai::whereIn('nip', ['198001012005011001', '198001012005011002', '198001012005011003'])->count())->toBe(3)
        ->and(Pegawai::where('nip', '198001012005011004')->exists())->toBeFalse()
        ->and(BarisImporGagal::count())->toBe(1);
});

test('admin-prodi tidak melihat tombol impor', function () {
    $prodi = Prodi::firstWhere('kode', 'PMAT');
    $user = adminImpor(Peran::AdminProdi);
    $user->update(['prodi_id' => $prodi->id]);
    $this->actingAs($user);

    Livewire::test(ListPegawai::class)->assertActionHidden(ImportAction::class);

    $this->actingAs(adminImpor(Peran::AdminKepegawaian));
    Livewire::test(ListPegawai::class)->assertActionVisible(ImportAction::class);
});

test('buat akun pegawai memberi peran, mengisi user_id, dan mengirim notifikasi reset', function () {
    Notification::fake();
    $pegawai = Pegawai::factory()->create(['email_unsil' => 'dosen.baru@unsil.ac.id', 'nip' => '198501012010012001']);

    $user = app(BuatAkunPegawai::class)->handle($pegawai);

    expect($user->hasRole(Peran::Dosen->value))->toBeTrue()
        ->and($pegawai->fresh()->user_id)->toBe($user->id)
        ->and($user->prodi_id)->toBe($pegawai->prodi_id)
        ->and($user->nip)->toBe('198501012010012001');
    Notification::assertSentTo($user, AkunSwalayanDibuat::class);
});

test('buat akun tendik memberi peran tendik', function () {
    Notification::fake();
    $pegawai = Pegawai::factory()->tendik()->create(['email_unsil' => 'tendik@unsil.ac.id']);

    expect(app(BuatAkunPegawai::class)->handle($pegawai)->hasRole(Peran::Tendik->value))->toBeTrue();
});

test('buat akun ditolak bila sudah punya akun, tanpa surel, atau surel dipakai', function () {
    Notification::fake();
    $sudah = Pegawai::factory()->create(['user_id' => User::factory()->create()->id]);
    $tanpaSurel = Pegawai::factory()->create(['email_unsil' => null]);
    User::factory()->create(['email' => 'dipakai@unsil.ac.id']);
    $bentrok = Pegawai::factory()->create(['email_unsil' => 'dipakai@unsil.ac.id']);

    foreach ([$sudah, $tanpaSurel, $bentrok] as $pegawai) {
        expect(fn () => app(BuatAkunPegawai::class)->handle($pegawai))->toThrow(ValidationException::class);
    }
});

<?php

use App\Actions\Usulan\AjukanUsulanPerubahan;
use App\Actions\Usulan\BuatUsulanPerubahan;
use App\Actions\Usulan\KembalikanUsulanPerubahan;
use App\Actions\Usulan\SetujuiUsulanPerubahan;
use App\Actions\Usulan\TampilkanDataUsulanSensitif;
use App\Actions\Usulan\TolakUsulanPerubahan;
use App\Enums\JenisUsulan;
use App\Enums\Peran;
use App\Enums\StatusUsulan;
use App\Exceptions\KonflikDataUsulan;
use App\Exceptions\TransisiUsulanTidakSah;
use App\Exceptions\UsulanSedangDiproses;
use App\Filament\Admin\Resources\UsulanPerubahan\Pages\ListUsulanPerubahan;
use App\Filament\Admin\Resources\UsulanPerubahan\Pages\ViewUsulanPerubahan;
use App\Filament\Admin\Resources\UsulanPerubahan\UsulanPerubahanResource;
use App\Models\Aktivitas;
use App\Models\JabatanFungsional;
use App\Models\Pegawai;
use App\Models\Prodi;
use App\Models\RiwayatJabatanFungsional;
use App\Models\Sertifikasi;
use App\Models\User;
use App\Models\UsulanPerubahan;
use App\Notifications\UsulanDiajukan;
use App\Notifications\UsulanDiputuskan;
use Database\Seeders\KonfigurasiSeeder;
use Database\Seeders\MasterJabatanSeeder;
use Database\Seeders\MasterPendukungSeeder;
use Database\Seeders\PeranDanIzinSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(PeranDanIzinSeeder::class);
    $this->seed(KonfigurasiSeeder::class);
    $this->seed(MasterPendukungSeeder::class);
    $this->seed(MasterJabatanSeeder::class);
});

function verifikator(Peran $peran = Peran::AdminKepegawaian, ?Prodi $prodi = null): User
{
    return tap(User::factory()->create([
        'email' => $peran->value.fake()->unique()->numerify('####').'@unsil.ac.id',
        'app_authentication_secret' => 'ABCDEFGHIJKLMNOP',
        'prodi_id' => $prodi?->id,
    ]), fn (User $u) => $u->assignRole($peran->value));
}

/** @return array<int, mixed> */
function pengusulDosen(array $pegawai = []): array
{
    $user = User::factory()->create(['email' => 'dosen'.fake()->unique()->numerify('####').'@unsil.ac.id']);
    $user->assignRole(Peran::Dosen->value);

    return [$user, Pegawai::factory()->create($pegawai + ['user_id' => $user->id])];
}

function usulanDiajukan(User $user, Pegawai $pegawai, array $data = ['no_hp' => '082222222222']): UsulanPerubahan
{
    $usulan = app(BuatUsulanPerubahan::class)->handle($user, $pegawai, JenisUsulan::UbahBiodata, 'pegawai', $pegawai->id, $data, null, 'Koreksi');

    return app(AjukanUsulanPerubahan::class)->handle($usulan, $user);
}

test('setujui ubah no_hp mengubah pegawai, menyimpan verifikator dan mengirim notifikasi ke pengusul', function () {
    Notification::fake();
    [$user, $pegawai] = pengusulDosen(['no_hp' => '081111111111']);
    $admin = verifikator();
    $usulan = usulanDiajukan($user, $pegawai);

    $hasil = app(SetujuiUsulanPerubahan::class)->handle($usulan, $admin);

    expect($pegawai->fresh()->no_hp)->toBe('082222222222')
        ->and($hasil->status)->toBe(StatusUsulan::Disetujui)->and($hasil->diverifikasi_oleh)->toBe($admin->id)
        ->and($hasil->diverifikasi_at)->not->toBeNull()->and($hasil->diterapkan_at)->not->toBeNull()
        ->and($hasil->kunci_aktif)->toBeNull();
    Notification::assertSentTo($user, UsulanDiputuskan::class);
});

test('mengajukan usulan memberi tahu semua admin kepegawaian', function () {
    Notification::fake();
    $admin1 = verifikator();
    $admin2 = verifikator();
    verifikator(Peran::Pimpinan);
    [$user, $pegawai] = pengusulDosen();

    usulanDiajukan($user, $pegawai);

    Notification::assertSentTo([$admin1, $admin2], UsulanDiajukan::class);
    Notification::assertCount(2);
});

test('setujui tambah riwayat jabatan fungsional membuat riwayat bersumber usulan dan menyinkron jabatan', function () {
    Notification::fake();
    [$user, $pegawai] = pengusulDosen();
    $lektor = JabatanFungsional::firstWhere('kode', 'lektor');
    $usulan = app(BuatUsulanPerubahan::class)->handle($user, $pegawai, JenisUsulan::TambahRiwayat, 'riwayat_jabatan_fungsional', null, [
        'jabatan_fungsional_id' => $lektor->id, 'tmt' => '2024-03-01', 'nomor_sk' => 'SK/LEKTOR/1',
        'tautan' => ['sk' => 'https://drive.google.com/file/d/1AbCdEfGhIjKlMnOpQrStUvWxYz012345/view'],
    ], ['url' => 'https://drive.google.com/file/d/9ZyXwVuTsRqPoNmLkJiHgFeDcBa987654/view', 'konfirmasi' => true]);
    app(AjukanUsulanPerubahan::class)->handle($usulan, $user);

    $hasil = app(SetujuiUsulanPerubahan::class)->handle($usulan, verifikator());

    $riwayat = RiwayatJabatanFungsional::where('pegawai_id', $pegawai->id)->first();
    expect($riwayat->sumber)->toBe('usulan')->and($riwayat->is_terkini)->toBeTrue()
        ->and($pegawai->fresh()->jabatan_fungsional_id)->toBe($lektor->id)
        ->and($riwayat->tautan('sk')->url)->toContain('1AbCdEfGhIjKl')
        ->and(collect($hasil->snapshot_tautan)->pluck('peran')->all())->toBe(['bukti', 'diterapkan'])
        ->and($hasil->snapshot_tautan[0]['drive_file_id'])->toBe('9ZyXwVuTsRqPoNmLkJiHgFeDcBa987654');
});

test('setujui hapus riwayat menghapus target', function () {
    Notification::fake();
    [$user, $pegawai] = pengusulDosen();
    $sertifikat = Sertifikasi::factory()->create(['pegawai_id' => $pegawai->id]);
    $usulan = app(BuatUsulanPerubahan::class)->handle($user, $pegawai, JenisUsulan::HapusRiwayat, 'sertifikasi', $sertifikat->id, [], null, 'Salah');
    app(AjukanUsulanPerubahan::class)->handle($usulan, $user);

    app(SetujuiUsulanPerubahan::class)->handle($usulan, verifikator());

    expect(Sertifikasi::find($sertifikat->id))->toBeNull();
});

test('tolak tanpa catatan ditolak validasi dan dengan catatan mengubah status tanpa mengubah data', function () {
    Notification::fake();
    [$user, $pegawai] = pengusulDosen(['no_hp' => '081111111111']);
    $usulan = usulanDiajukan($user, $pegawai);

    expect(fn () => app(TolakUsulanPerubahan::class)->handle($usulan, verifikator(), 'singkat'))->toThrow(ValidationException::class);

    $hasil = app(TolakUsulanPerubahan::class)->handle($usulan, verifikator(), 'Bukti tidak sesuai dengan data.');

    expect($hasil->status)->toBe(StatusUsulan::Ditolak)->and($hasil->catatan_verifikator)->toBe('Bukti tidak sesuai dengan data.')
        ->and($pegawai->fresh()->no_hp)->toBe('081111111111');
    Notification::assertSentTo($user, UsulanDiputuskan::class);
});

test('kembalikan memungkinkan pengusul mengirim ulang', function () {
    Notification::fake();
    [$user, $pegawai] = pengusulDosen();
    $usulan = usulanDiajukan($user, $pegawai);

    expect(fn () => app(KembalikanUsulanPerubahan::class)->handle($usulan, verifikator(), ''))->toThrow(ValidationException::class);

    $dikembalikan = app(KembalikanUsulanPerubahan::class)->handle($usulan, verifikator(), 'Lengkapi nomor SK terlebih dahulu.');
    expect($dikembalikan->status)->toBe(StatusUsulan::Dikembalikan)->and($dikembalikan->kunci_aktif)->not->toBeNull();

    $lagi = app(AjukanUsulanPerubahan::class)->handle($dikembalikan, $user);
    expect($lagi->status)->toBe(StatusUsulan::Diajukan);
});

test('lock redis menolak pemrosesan bersamaan', function () {
    [$user, $pegawai] = pengusulDosen();
    $usulan = usulanDiajukan($user, $pegawai);
    $lock = Cache::lock('sdm:lock:usulan:'.$usulan->id, 30);
    expect($lock->get())->toBeTrue();

    expect(fn () => app(SetujuiUsulanPerubahan::class)->handle($usulan, verifikator()))->toThrow(UsulanSedangDiproses::class);

    $lock->release();
    Notification::fake();
    expect(app(SetujuiUsulanPerubahan::class)->handle($usulan, verifikator())->status)->toBe(StatusUsulan::Disetujui);
});

test('hanya usulan berstatus diajukan yang dapat diputuskan', function () {
    [$user, $pegawai] = pengusulDosen();
    $draf = app(BuatUsulanPerubahan::class)->handle($user, $pegawai, JenisUsulan::UbahBiodata, 'pegawai', $pegawai->id, ['no_hp' => '0833']);

    expect(fn () => app(SetujuiUsulanPerubahan::class)->handle($draf, verifikator()))
        ->toThrow(TransisiUsulanTidakSah::class);
});

test('konflik data: setujui tanpa konfirmasi gagal, dengan konfirmasi berhasil', function () {
    Notification::fake();
    [$user, $pegawai] = pengusulDosen(['no_hp' => '081111111111']);
    $usulan = usulanDiajukan($user, $pegawai);
    $this->travel(2)->seconds();
    $pegawai->update(['alamat' => 'Alamat diubah admin']);

    expect($usulan->fresh()->adaKonflik())->toBeTrue()
        ->and(fn () => app(SetujuiUsulanPerubahan::class)->handle($usulan, verifikator()))->toThrow(KonflikDataUsulan::class)
        ->and($pegawai->fresh()->no_hp)->toBe('081111111111');

    $hasil = app(SetujuiUsulanPerubahan::class)->handle($usulan, verifikator(), true);
    expect($hasil->status)->toBe(StatusUsulan::Disetujui)->and($pegawai->fresh()->no_hp)->toBe('082222222222');
});

test('admin-prodi tidak dapat menyetujui dan hanya melihat usulan prodinya', function () {
    Notification::fake();
    $pmat = Prodi::factory()->create();
    $pbio = Prodi::factory()->create();
    [$user1, $pegawaiPmat] = pengusulDosen(['prodi_id' => $pmat->id]);
    [$user2, $pegawaiPbio] = pengusulDosen(['prodi_id' => $pbio->id]);
    $usulanPmat = usulanDiajukan($user1, $pegawaiPmat);
    $usulanPbio = usulanDiajukan($user2, $pegawaiPbio);
    $adminProdi = verifikator(Peran::AdminProdi, $pmat);

    expect($adminProdi->can('verifikasi', $usulanPmat))->toBeFalse()
        ->and($adminProdi->can('view', $usulanPmat))->toBeTrue()
        ->and($adminProdi->can('view', $usulanPbio))->toBeFalse();

    $this->actingAs($adminProdi)->get(UsulanPerubahanResource::getUrl('view', ['record' => $usulanPmat]))->assertOk();
    $status = $this->actingAs($adminProdi)->get(UsulanPerubahanResource::getUrl('view', ['record' => $usulanPbio]))->status();
    expect($status)->toBeIn([403, 404]);

    Livewire::actingAs($adminProdi)->test(ViewUsulanPerubahan::class, ['record' => $usulanPmat->getKey()])
        ->assertActionHidden('setujui')->assertActionHidden('tolak')->assertActionHidden('kembalikan');
});

test('dosen tidak dapat membuka resource usulan admin', function () {
    [$user] = pengusulDosen();

    $this->actingAs($user)->get(UsulanPerubahanResource::getUrl('index'))->assertForbidden();
});

test('halaman view admin menampilkan diff dan aksi setujui lewat ui', function () {
    Notification::fake();
    [$user, $pegawai] = pengusulDosen(['no_hp' => '081111111111']);
    $usulan = usulanDiajukan($user, $pegawai);
    $admin = verifikator();

    Livewire::actingAs($admin)->test(ViewUsulanPerubahan::class, ['record' => $usulan->getKey()])
        ->assertSee('081111111111')->assertSee('082222222222')
        ->callAction('setujui')
        ->assertNotified('Usulan disetujui dan diterapkan.');

    expect($pegawai->fresh()->no_hp)->toBe('082222222222');
});

test('aksi tolak lewat ui mensyaratkan catatan 10 karakter', function () {
    Notification::fake();
    [$user, $pegawai] = pengusulDosen();
    $usulan = usulanDiajukan($user, $pegawai);

    Livewire::actingAs(verifikator())->test(ViewUsulanPerubahan::class, ['record' => $usulan->getKey()])
        ->callAction('tolak', ['catatan' => 'pendek'])
        ->assertHasActionErrors(['catatan']);

    expect($usulan->fresh()->status)->toBe(StatusUsulan::Diajukan);
});

test('daftar usulan default memfilter status diajukan dan badge navigasi menghitungnya', function () {
    Notification::fake();
    [$user, $pegawai] = pengusulDosen();
    $diajukan = usulanDiajukan($user, $pegawai);
    [$user2, $pegawai2] = pengusulDosen();
    $draf = app(BuatUsulanPerubahan::class)->handle($user2, $pegawai2, JenisUsulan::UbahBiodata, 'pegawai', $pegawai2->id, ['no_hp' => '0877']);
    $this->actingAs(verifikator());

    Livewire::test(ListUsulanPerubahan::class)
        ->loadTable()->assertCanSeeTableRecords([$diajukan])->assertCanNotSeeTableRecords([$draf]);

    expect(UsulanPerubahanResource::getNavigationBadge())->toBe('1');
});

test('tampil data sensitif usulan berizin, tercatat, dan dibatasi', function () {
    Notification::fake();
    [$user, $pegawai] = pengusulDosen(['nik' => '3278010101900001']);
    $usulan = app(BuatUsulanPerubahan::class)->handle($user, $pegawai, JenisUsulan::UbahBiodata, 'pegawai', $pegawai->id,
        ['nik' => '3278010101900002'], ['url' => 'https://drive.google.com/file/d/1AbCdEfGhIjKlMnOpQrStUvWxYz012345/view', 'konfirmasi' => true]);
    $admin = verifikator();
    $prodi = Prodi::factory()->create();

    $hasil = app(TampilkanDataUsulanSensitif::class)->handle($admin, $usulan);

    expect($hasil['nik'])->toBe(['lama' => '3278010101900001', 'baru' => '3278010101900002'])
        ->and(Aktivitas::where('log_name', 'akses-sensitif')->count())->toBe(1)
        ->and(fn () => app(TampilkanDataUsulanSensitif::class)->handle(verifikator(Peran::AdminProdi, $prodi), $usulan))
        ->toThrow(AuthorizationException::class);
});

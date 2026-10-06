<?php

use App\Actions\Usulan\KirimUsulanSaya;
use App\Enums\JenisUsulan;
use App\Enums\Peran;
use App\Enums\StatusUsulan;
use App\Filament\Swalayan\Pages\ProfilSaya;
use App\Filament\Swalayan\Pages\UsulanSaya;
use App\Models\JenjangPendidikan;
use App\Models\Pegawai;
use App\Models\PersetujuanPrivasi;
use App\Models\Sertifikasi;
use App\Models\User;
use App\Models\UsulanPerubahan;
use App\Support\Konfigurasi;
use Database\Seeders\KonfigurasiSeeder;
use Database\Seeders\MasterPendukungSeeder;
use Database\Seeders\PeranDanIzinSeeder;
use Filament\Actions\Testing\TestAction;
use Illuminate\Auth\Access\AuthorizationException;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(PeranDanIzinSeeder::class);
    $this->seed(KonfigurasiSeeder::class);
    $this->seed(MasterPendukungSeeder::class);
});

/** @return array<int, mixed> */
function dosenSiap(array $pegawai = []): array
{
    $user = User::factory()->create(['email' => 'dosen'.fake()->unique()->numerify('####').'@unsil.ac.id']);
    $user->assignRole(Peran::Dosen->value);
    $p = Pegawai::factory()->create($pegawai + ['user_id' => $user->id]);
    PersetujuanPrivasi::create(['user_id' => $user->id, 'versi' => Konfigurasi::get('versi_kebijakan_privasi'), 'disetujui_at' => now()]);

    return [$user, $p];
}

const BUKTI_VALID = 'https://drive.google.com/file/d/1AbCdEfGhIjKlMnOpQrStUvWxYz012345/view';

test('dosen mengajukan ubah alamat sehingga usulan diajukan tanpa mengubah data', function () {
    [$user, $pegawai] = dosenSiap(['alamat' => 'Jl. Lama 1']);

    Livewire::actingAs($user)->test(ProfilSaya::class)
        ->callAction('ajukan_biodata', ['alamat' => 'Jl. Baru 2', 'alasan' => 'Pindah rumah'])
        ->assertNotified('Usulan terkirim dan menunggu verifikasi admin kepegawaian.');

    $usulan = UsulanPerubahan::first();
    expect($usulan->status)->toBe(StatusUsulan::Diajukan)->and($usulan->data_baru)->toBe(['alamat' => 'Jl. Baru 2'])
        ->and($usulan->data_lama['alamat'])->toBe('Jl. Lama 1')->and($usulan->alasan)->toBe('Pindah rumah')
        ->and($usulan->diajukan_at)->not->toBeNull()
        ->and($pegawai->fresh()->alamat)->toBe('Jl. Lama 1');
});

test('mengajukan tanpa perubahan apa pun ditolak', function () {
    [$user, $pegawai] = dosenSiap(['alamat' => 'Jl. Lama 1', 'no_hp' => '0811']);

    Livewire::actingAs($user)->test(ProfilSaya::class)
        ->callAction('ajukan_biodata', ['alamat' => 'Jl. Lama 1', 'no_hp' => '0811'])
        ->assertNotified('Usulan tidak dapat dikirim');

    expect(UsulanPerubahan::count())->toBe(0);
});

test('ubah nik tanpa bukti ditolak dan dengan bukti diterima', function () {
    [$user, $pegawai] = dosenSiap(['nik' => '3278010101900001']);

    Livewire::actingAs($user)->test(ProfilSaya::class)
        ->callAction('ajukan_biodata', ['nik' => '3278010101900002'])
        ->assertNotified('Usulan tidak dapat dikirim');
    expect(UsulanPerubahan::count())->toBe(0);

    Livewire::actingAs($user)->test(ProfilSaya::class)
        ->callAction('ajukan_biodata', ['nik' => '3278010101900002', 'tautan_bukti' => BUKTI_VALID, 'tautan_bukti_konfirmasi' => true])
        ->assertNotified('Usulan terkirim dan menunggu verifikasi admin kepegawaian.');

    $usulan = UsulanPerubahan::first();
    expect($usulan->tautan('bukti_usulan')->is_sensitif)->toBeTrue()->and($usulan->data_baru)->toBe(['nik' => '3278010101900002'])
        ->and($pegawai->fresh()->nik)->toBe('3278010101900001');
});

test('nik dikosongkan tidak dianggap perubahan', function () {
    [$user] = dosenSiap(['nik' => '3278010101900001', 'no_hp' => '0811']);

    Livewire::actingAs($user)->test(ProfilSaya::class)
        ->callAction('ajukan_biodata', ['nik' => '', 'no_hp' => '0822']);

    expect(UsulanPerubahan::first()->data_baru)->toBe(['no_hp' => '0822']);
});

test('usulkan tambah pendidikan s3 menyimpan target kosong dan data lengkap', function () {
    [$user] = dosenSiap();
    $s3 = JenjangPendidikan::firstWhere('kode', 'S3');

    Livewire::actingAs($user)->test(ProfilSaya::class)
        ->callAction('usulkan_tambah_riwayat_pendidikan', [
            'jenjang_pendidikan_id' => $s3->id, 'nama_pt' => 'UPI', 'tahun_lulus' => 2024,
            'tautan_ijazah' => BUKTI_VALID, 'tautan_ijazah_konfirmasi' => true,
            'tautan_bukti' => BUKTI_VALID, 'tautan_bukti_konfirmasi' => true,
        ])
        ->assertNotified('Usulan terkirim dan menunggu verifikasi admin kepegawaian.');

    $usulan = UsulanPerubahan::first();
    expect($usulan->target_id)->toBeNull()->and($usulan->jenis)->toBe(JenisUsulan::TambahRiwayat)
        ->and($usulan->data_baru['nama_pt'])->toBe('UPI')->and($usulan->data_baru['tautan']['ijazah'])->toBe(BUKTI_VALID);
});

test('simpan sebagai draf tidak mengirim', function () {
    [$user] = dosenSiap();

    Livewire::actingAs($user)->test(ProfilSaya::class)
        ->callAction('ajukan_biodata', ['no_hp' => '0899', 'simpan_draf' => true])
        ->assertNotified('Usulan disimpan sebagai draf.');

    expect(UsulanPerubahan::first()->status)->toBe(StatusUsulan::Draf);
});

test('usulkan hapus sertifikasi milik dosen lain ditolak walau id dimanipulasi', function () {
    [$user, $pegawai] = dosenSiap();
    $milikLain = Sertifikasi::factory()->create();

    expect(fn () => app(KirimUsulanSaya::class)->handle($user, $pegawai, JenisUsulan::HapusRiwayat, 'sertifikasi', $milikLain->id, ['alasan' => 'x']))
        ->toThrow(AuthorizationException::class);
    expect(UsulanPerubahan::count())->toBe(0);
});

test('usulkan hapus milik sendiri berjalan', function () {
    [$user, $pegawai] = dosenSiap();
    $milik = Sertifikasi::factory()->create(['pegawai_id' => $pegawai->id]);

    $usulan = app(KirimUsulanSaya::class)->handle($user, $pegawai, JenisUsulan::HapusRiwayat, 'sertifikasi', $milik->id, ['alasan' => 'Salah input']);

    expect($usulan->status)->toBe(StatusUsulan::Diajukan)->and($usulan->jenis)->toBe(JenisUsulan::HapusRiwayat)->and($milik->fresh())->not->toBeNull();
});

test('membatalkan usulan diajukan mengubah status menjadi dibatalkan', function () {
    [$user, $pegawai] = dosenSiap();
    $usulan = UsulanPerubahan::factory()->create(['pegawai_id' => $pegawai->id, 'diajukan_oleh' => $user->id, 'status' => 'diajukan', 'kunci_aktif' => 'k1']);

    Livewire::actingAs($user)->test(UsulanSaya::class)
        ->assertCanSeeTableRecords([$usulan])
        ->callAction(TestAction::make('batalkan')->table($usulan));

    expect($usulan->fresh()->status)->toBe(StatusUsulan::Dibatalkan)->and($usulan->fresh()->kunci_aktif)->toBeNull();
});

test('usulan saya hanya menampilkan usulan milik sendiri', function () {
    [$user, $pegawai] = dosenSiap();
    $milik = UsulanPerubahan::factory()->create(['pegawai_id' => $pegawai->id, 'diajukan_oleh' => $user->id]);
    $lain = UsulanPerubahan::factory()->create();

    Livewire::actingAs($user)->test(UsulanSaya::class)->assertCanSeeTableRecords([$milik])->assertCanNotSeeTableRecords([$lain]);
});

test('ubah dan kirim ulang usulan yang dikembalikan', function () {
    [$user, $pegawai] = dosenSiap(['no_hp' => '0811']);
    $usulan = UsulanPerubahan::factory()->create([
        'pegawai_id' => $pegawai->id, 'diajukan_oleh' => $user->id, 'status' => 'dikembalikan',
        'data_lama' => ['no_hp' => '0811'], 'data_baru' => ['no_hp' => '0822'], 'kunci_aktif' => 'k2', 'catatan_verifikator' => 'Perbaiki nomor',
    ]);

    Livewire::actingAs($user)->test(UsulanSaya::class)
        ->callAction(TestAction::make('ubah_kirim_ulang')->table($usulan), ['no_hp' => '0833', 'nik' => '', 'alasan' => 'Sudah diperbaiki'])
        ->assertNotified('Usulan terkirim dan menunggu verifikasi admin kepegawaian.');

    $segar = $usulan->fresh();
    expect($segar->status)->toBe(StatusUsulan::Diajukan)->and($segar->data_baru['no_hp'])->toBe('0833')->and($segar->alasan)->toBe('Sudah diperbaiki');
});

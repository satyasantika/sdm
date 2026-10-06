<?php

use App\Actions\Usulan\AjukanUsulanPerubahan;
use App\Actions\Usulan\BatalkanUsulanPerubahan;
use App\Actions\Usulan\BuatUsulanPerubahan;
use App\Actions\Usulan\UbahStatusUsulan;
use App\Enums\JenisUsulan;
use App\Enums\Peran;
use App\Enums\StatusUsulan;
use App\Exceptions\TransisiUsulanTidakSah;
use App\Models\JenjangPendidikan;
use App\Models\Pegawai;
use App\Models\Sertifikasi;
use App\Models\User;
use App\Models\UsulanPerubahan;
use App\Support\RegistriTargetUsulan;
use Database\Seeders\MasterPendukungSeeder;
use Database\Seeders\PeranDanIzinSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->seed(PeranDanIzinSeeder::class);
    $this->seed(MasterPendukungSeeder::class);
});

/** @return array{0: User, 1: Pegawai} */
function dosenDenganPegawai(array $pegawai = []): array
{
    $user = User::factory()->create(['email' => 'dosen'.fake()->unique()->numerify('####').'@unsil.ac.id']);
    $user->assignRole(Peran::Dosen->value);

    return [$user, Pegawai::factory()->create($pegawai + ['user_id' => $user->id])];
}

function usulkanBiodata(User $user, Pegawai $pegawai, array $data, ?array $bukti = null): UsulanPerubahan
{
    return app(BuatUsulanPerubahan::class)->handle($user, $pegawai, JenisUsulan::UbahBiodata, 'pegawai', $pegawai->id, $data, $bukti, 'Koreksi data');
}

test('usulan ubah no_hp tidak mengubah data pegawai dan menyimpan data lama', function () {
    [$user, $pegawai] = dosenDenganPegawai(['no_hp' => '081111111111']);

    $usulan = usulkanBiodata($user, $pegawai, ['no_hp' => '082222222222']);

    expect($pegawai->fresh()->no_hp)->toBe('081111111111')
        ->and($usulan->status)->toBe(StatusUsulan::Draf)
        ->and($usulan->data_lama['no_hp'])->toBe('081111111111')
        ->and($usulan->data_baru)->toBe(['no_hp' => '082222222222'])
        ->and($usulan->target_updated_at)->not->toBeNull();
});

test('kolom di luar daftar putih dibuang dari data baru', function () {
    [$user, $pegawai] = dosenDenganPegawai();

    $usulan = usulkanBiodata($user, $pegawai, ['no_hp' => '082222222222', 'prodi_id' => 'x', 'status_aktif' => 'pensiun', 'nip' => '1', 'jabatan_fungsional_id' => 'y']);

    expect(array_keys($usulan->data_baru))->toBe(['no_hp']);
});

test('registri tidak mengizinkan kolom sistem', function () {
    foreach (['jenis_pegawai', 'status_kepegawaian_id', 'prodi_id', 'unit_kerja_id', 'status_aktif', 'nip', 'jabatan_fungsional_id', 'golongan_id'] as $kolom) {
        expect(RegistriTargetUsulan::kolomBoleh('pegawai'))->not->toContain($kolom);
    }

    foreach (RegistriTargetUsulan::tabel() as $tabel) {
        expect(RegistriTargetUsulan::kolomBoleh($tabel))->not->toContain('is_terkini')->not->toContain('sumber')->not->toContain('pegawai_id');
    }
});

test('target milik pegawai lain ditolak dan hanya pemilik yang boleh mengusulkan', function () {
    [$user, $pegawai] = dosenDenganPegawai();
    $sertifikatLain = Sertifikasi::factory()->create();

    expect(fn () => app(BuatUsulanPerubahan::class)->handle($user, $pegawai, JenisUsulan::HapusRiwayat, 'sertifikasi', $sertifikatLain->id, [], null, null))
        ->toThrow(AuthorizationException::class);

    [, $pegawaiLain] = dosenDenganPegawai();
    expect(fn () => usulkanBiodata($user, $pegawaiLain, ['no_hp' => '0822']))->toThrow(AuthorizationException::class);
});

test('tabel di luar registri dan jenis tidak sesuai ditolak', function () {
    [$user, $pegawai] = dosenDenganPegawai();

    expect(fn () => app(BuatUsulanPerubahan::class)->handle($user, $pegawai, JenisUsulan::TambahRiwayat, 'users', null, ['name' => 'x']))->toThrow(ValidationException::class)
        ->and(fn () => app(BuatUsulanPerubahan::class)->handle($user, $pegawai, JenisUsulan::TambahRiwayat, 'pegawai', null, ['no_hp' => '1']))->toThrow(ValidationException::class);
});

test('usulan kedua untuk target sama saat yang pertama aktif ditolak dan boleh lagi setelah dibatalkan', function () {
    [$user, $pegawai] = dosenDenganPegawai(['no_hp' => '081111111111']);
    $pertama = usulkanBiodata($user, $pegawai, ['no_hp' => '082222222222']);

    try {
        usulkanBiodata($user, $pegawai, ['alamat' => 'Jl. Baru 1']);
        $this->fail('seharusnya ditolak');
    } catch (ValidationException $e) {
        expect($e->errors()['usulan'][0])->toBe('Masih ada usulan aktif untuk data ini.');
    }

    app(BatalkanUsulanPerubahan::class)->handle($pertama, $user);

    expect($pertama->fresh()->kunci_aktif)->toBeNull()
        ->and(usulkanBiodata($user, $pegawai, ['alamat' => 'Jl. Baru 1']))->toBeInstanceOf(UsulanPerubahan::class);
});

test('transisi tidak sah melempar exception dan setiap transisi menulis riwayat', function () {
    [$user, $pegawai] = dosenDenganPegawai();
    $usulan = usulkanBiodata($user, $pegawai, ['no_hp' => '082222222222']);

    app(AjukanUsulanPerubahan::class)->handle($usulan, $user);
    expect($usulan->fresh()->diajukan_at)->not->toBeNull();

    app(UbahStatusUsulan::class)->handle($usulan->fresh(), StatusUsulan::Disetujui, $user);
    expect(fn () => app(AjukanUsulanPerubahan::class)->handle($usulan->fresh(), $user))->toThrow(TransisiUsulanTidakSah::class);

    $riwayat = $usulan->riwayatStatus()->get();
    expect($riwayat->map(fn ($r) => $r->ke_status->value)->all())->toBe(['draf', 'diajukan', 'disetujui'])
        ->and($riwayat[1]->dari_status)->toBe(StatusUsulan::Draf)
        ->and($usulan->fresh()->kunci_aktif)->toBeNull();
});

test('status enum mengikuti diagram transisi', function () {
    expect(StatusUsulan::Draf->bolehBerpindahKe(StatusUsulan::Diajukan))->toBeTrue()
        ->and(StatusUsulan::Draf->bolehBerpindahKe(StatusUsulan::Disetujui))->toBeFalse()
        ->and(StatusUsulan::Dikembalikan->bolehBerpindahKe(StatusUsulan::Diajukan))->toBeTrue()
        ->and(StatusUsulan::Disetujui->bolehBerpindahKe(StatusUsulan::Diajukan))->toBeFalse()
        ->and(StatusUsulan::Ditolak->isFinal())->toBeTrue()
        ->and(StatusUsulan::Dikembalikan->isAktif())->toBeTrue();
});

test('data baru dan data lama terenkripsi di db', function () {
    [$user, $pegawai] = dosenDenganPegawai(['no_hp' => '081111111111']);
    $usulan = usulkanBiodata($user, $pegawai, ['no_hp' => '082222222222']);

    $mentah = DB::table('usulan_perubahan')->where('id', $usulan->id)->first();

    expect($mentah->data_baru)->not->toContain('082222222222')->and($mentah->data_baru)->toStartWith('eyJ')
        ->and($mentah->data_lama)->not->toContain('081111111111');
});

test('tanpa perubahan atau validasi gagal ditolak', function () {
    [$user, $pegawai] = dosenDenganPegawai(['no_hp' => '081111111111']);

    expect(fn () => usulkanBiodata($user, $pegawai, ['no_hp' => '081111111111']))->toThrow(ValidationException::class)
        ->and(fn () => usulkanBiodata($user, $pegawai, ['email_pribadi' => 'bukan-email']))->toThrow(ValidationException::class);
});

test('ubah nik wajib bukti dan bukti tersimpan sebagai tautan sensitif', function () {
    [$user, $pegawai] = dosenDenganPegawai(['nik' => '3278010101900001']);

    expect(fn () => usulkanBiodata($user, $pegawai, ['nik' => '3278010101900002']))->toThrow(ValidationException::class);

    $usulan = usulkanBiodata($user, $pegawai, ['nik' => '3278010101900002'], [
        'url' => 'https://drive.google.com/file/d/1AbCdEfGhIjKlMnOpQrStUvWxYz012345/view', 'konfirmasi' => true,
    ]);

    expect($usulan->tautan('bukti_usulan')->is_sensitif)->toBeTrue()->and($usulan->data_lama['nik'])->toBe('3278010101900001');
});

test('tambah riwayat pendidikan s3 menyimpan target kosong dan data lengkap', function () {
    [$user, $pegawai] = dosenDenganPegawai();
    $s3 = JenjangPendidikan::firstWhere('kode', 'S3');

    $usulan = app(BuatUsulanPerubahan::class)->handle($user, $pegawai, JenisUsulan::TambahRiwayat, 'riwayat_pendidikan', null, [
        'jenjang_pendidikan_id' => $s3->id, 'nama_pt' => 'UPI', 'tahun_lulus' => 2024, 'prodi_id' => 'abaikan',
    ], ['url' => 'https://drive.google.com/file/d/1AbCdEfGhIjKlMnOpQrStUvWxYz012345/view', 'konfirmasi' => true]);

    expect($usulan->target_id)->toBeNull()->and($usulan->data_lama)->toBeNull()
        ->and($usulan->data_baru)->toBe(['jenjang_pendidikan_id' => $s3->id, 'nama_pt' => 'UPI', 'tahun_lulus' => 2024])
        ->and(str_starts_with((string) $usulan->kunci_aktif, $pegawai->id.':riwayat_pendidikan:baru-'))->toBeTrue();
});

test('usulan tambah riwayat berulang diperbolehkan karena target baru berbeda', function () {
    [$user, $pegawai] = dosenDenganPegawai();
    $data = ['nama' => 'Penghargaan A', 'tingkat' => 'nasional'];

    foreach ([1, 2] as $i) {
        app(BuatUsulanPerubahan::class)->handle($user, $pegawai, JenisUsulan::TambahRiwayat, 'penghargaan', null, $data);
    }

    expect(UsulanPerubahan::count())->toBe(2);
});

test('hapus riwayat milik sendiri dan ubah riwayat dengan snapshot', function () {
    [$user, $pegawai] = dosenDenganPegawai();
    $sertifikat = Sertifikasi::factory()->create(['pegawai_id' => $pegawai->id, 'nama' => 'Sertifikat Lama']);

    $hapus = app(BuatUsulanPerubahan::class)->handle($user, $pegawai, JenisUsulan::HapusRiwayat, 'sertifikasi', $sertifikat->id, [], null, 'Salah input');
    expect($hapus->data_baru)->toBeNull()->and($hapus->data_lama['nama'])->toBe('Sertifikat Lama');
    app(BatalkanUsulanPerubahan::class)->handle($hapus, $user);

    $ubah = app(BuatUsulanPerubahan::class)->handle($user, $pegawai, JenisUsulan::UbahRiwayat, 'sertifikasi', $sertifikat->id, [
        'jenis_sertifikasi_id' => $sertifikat->jenis_sertifikasi_id, 'nama' => 'Sertifikat Baru',
    ], ['url' => 'https://drive.google.com/file/d/1AbCdEfGhIjKlMnOpQrStUvWxYz012345/view', 'konfirmasi' => true]);

    expect($ubah->data_baru['nama'])->toBe('Sertifikat Baru')->and($ubah->data_lama['nama'])->toBe('Sertifikat Lama');
});

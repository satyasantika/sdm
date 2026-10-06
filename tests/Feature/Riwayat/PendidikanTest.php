<?php

use App\Actions\Riwayat\SimpanRiwayatPendidikan;
use App\Enums\Peran;
use App\Filament\Admin\Resources\Pegawai\Pages\EditPegawai;
use App\Filament\Admin\Resources\Pegawai\RelationManagers\PendidikanRelationManager;
use App\Models\JenjangPendidikan;
use App\Models\Pegawai;
use App\Models\Prodi;
use App\Models\RiwayatPendidikan;
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

function jenjang(string $kode): JenjangPendidikan
{
    return JenjangPendidikan::firstWhere('kode', $kode);
}

function simpanPendidikan(Pegawai $pegawai, string $kodeJenjang, array $tambahan = []): RiwayatPendidikan
{
    return app(SimpanRiwayatPendidikan::class)->handle($pegawai, array_merge([
        'jenjang_pendidikan_id' => jenjang($kodeJenjang)->id, 'nama_pt' => 'Universitas Contoh',
        'tahun_masuk' => 2010, 'tahun_lulus' => 2014,
    ], $tambahan));
}

test('menambah s3 memindahkan penanda tertinggi dari s2 dan menghapus s3 mengembalikannya', function () {
    $pegawai = Pegawai::factory()->create();
    $s1 = simpanPendidikan($pegawai, 'S1');
    $s2 = simpanPendidikan($pegawai, 'S2', ['tahun_lulus' => 2018]);

    expect($s2->fresh()->is_pendidikan_tertinggi)->toBeTrue()->and($s1->fresh()->is_pendidikan_tertinggi)->toBeFalse();

    $s3 = simpanPendidikan($pegawai, 'S3', ['tahun_lulus' => 2024]);
    expect($s3->fresh()->is_pendidikan_tertinggi)->toBeTrue()
        ->and($s2->fresh()->is_pendidikan_tertinggi)->toBeFalse()
        ->and($pegawai->pendidikanTertinggi->id)->toBe($s3->id);

    app(SimpanRiwayatPendidikan::class)->hapus($s3);
    expect($s2->fresh()->is_pendidikan_tertinggi)->toBeTrue();
});

test('jenjang sama memilih tahun lulus terbaru', function () {
    $pegawai = Pegawai::factory()->create();
    simpanPendidikan($pegawai, 'S2', ['tahun_lulus' => 2015]);
    $baru = simpanPendidikan($pegawai, 'S2', ['tahun_lulus' => 2020]);

    expect(RiwayatPendidikan::where('pegawai_id', $pegawai->id)->where('is_pendidikan_tertinggi', true)->pluck('id')->all())->toBe([$baru->id]);
});

test('validasi ipk tahun lulus dan tahun masuk', function () {
    $pegawai = Pegawai::factory()->create();

    expect(fn () => simpanPendidikan($pegawai, 'S1', ['ipk' => 4.5]))->toThrow(ValidationException::class)
        ->and(fn () => simpanPendidikan($pegawai, 'S1', ['tahun_masuk' => 2015, 'tahun_lulus' => 2012]))->toThrow(ValidationException::class)
        ->and(fn () => simpanPendidikan($pegawai, 'S1', ['tahun_lulus' => (int) now()->year + 2]))->toThrow(ValidationException::class);

    expect(simpanPendidikan($pegawai, 'S1', ['ipk' => '3.75'])->fresh()->ipk)->toBe('3.75');
});

test('admin-prodi dapat menambah untuk dosen prodinya melalui relation manager dengan tautan ijazah', function () {
    $prodi = Prodi::factory()->create();
    $pegawai = Pegawai::factory()->create(['prodi_id' => $prodi->id]);
    $adminProdi = tap(User::factory()->create(['email' => 'ap@unsil.ac.id', 'prodi_id' => $prodi->id]), fn ($u) => $u->assignRole(Peran::AdminProdi->value));
    $this->actingAs($adminProdi);

    Livewire::test(PendidikanRelationManager::class, ['ownerRecord' => $pegawai, 'pageClass' => EditPegawai::class])
        ->callAction(TestAction::make('create')->table(), [
            'jenjang_pendidikan_id' => jenjang('S2')->id, 'nama_pt' => 'UPI', 'tahun_lulus' => 2016,
            'tautan_ijazah' => 'https://drive.google.com/file/d/1AbCdEfGhIjKlMnOpQrStUvWxYz012345/view',
            'tautan_ijazah_konfirmasi' => true,
        ])->assertHasNoFormErrors();

    $riwayat = RiwayatPendidikan::first();
    expect($riwayat->berkasIjazah()->is_sensitif)->toBeTrue()->and($riwayat->berkasTranskrip())->toBeNull()
        ->and($adminProdi->can('update', RiwayatPendidikan::factory()->create()))->toBeFalse();
});

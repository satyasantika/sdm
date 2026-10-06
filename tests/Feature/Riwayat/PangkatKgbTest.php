<?php

use App\Actions\Riwayat\SimpanRiwayatKgb;
use App\Actions\Riwayat\SimpanRiwayatPangkat;
use App\Enums\Peran;
use App\Filament\Admin\Resources\Pegawai\Pages\EditPegawai;
use App\Filament\Admin\Resources\Pegawai\RelationManagers\KgbRelationManager;
use App\Filament\Admin\Resources\Pegawai\RelationManagers\PangkatRelationManager;
use App\Models\Golongan;
use App\Models\Pegawai;
use App\Models\Prodi;
use App\Models\RiwayatKgb;
use App\Models\RiwayatPangkat;
use App\Models\StatusKepegawaian;
use App\Models\User;
use Database\Seeders\MasterKepegawaianSeeder;
use Database\Seeders\PeranDanIzinSeeder;
use Filament\Actions\Testing\TestAction;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(PeranDanIzinSeeder::class);
    $this->seed(MasterKepegawaianSeeder::class);
});

function pegawaiStatus(string $kode): Pegawai
{
    return Pegawai::factory()->create(['status_kepegawaian_id' => StatusKepegawaian::firstWhere('kode', $kode)->id]);
}

function gol(string $jenis, string $kode): Golongan
{
    return Golongan::where('jenis', $jenis)->where('kode', $kode)->first();
}

function simpanPangkat(Pegawai $pegawai, Golongan $golongan, string $tmt, array $tambahan = []): RiwayatPangkat
{
    return app(SimpanRiwayatPangkat::class)->handle($pegawai, array_merge([
        'golongan_id' => $golongan->id, 'tmt' => $tmt, 'nomor_sk' => 'SK/'.$tmt, 'jenis_kenaikan' => 'reguler',
    ], $tambahan));
}

function aktorPangkat(Peran $peran, ?Prodi $prodi = null): User
{
    return tap(User::factory()->create([
        'email' => $peran->value.fake()->unique()->numerify('###').'@unsil.ac.id',
        'app_authentication_secret' => 'ABCDEFGHIJKLMNOP',
        'prodi_id' => $prodi?->id,
    ]), fn (User $u) => $u->assignRole($peran->value));
}

test('pns menerima golongan pns dan menolak pppk; pppk sebaliknya', function () {
    $pns = pegawaiStatus('pns');
    $pppk = pegawaiStatus('pppk');

    expect(simpanPangkat($pns, gol('pns', 'III/a'), '2020-01-01')->golongan_id)->toBe(gol('pns', 'III/a')->id)
        ->and(fn () => simpanPangkat($pns, gol('pppk', 'IX'), '2021-01-01'))->toThrow(ValidationException::class)
        ->and(simpanPangkat($pppk, gol('pppk', 'IX'), '2020-01-01')->golongan_id)->toBe(gol('pppk', 'IX')->id)
        ->and(fn () => simpanPangkat($pppk, gol('pns', 'III/a'), '2021-01-01'))->toThrow(ValidationException::class);
});

test('non asn ditolak dengan pesan khusus', function () {
    $nonAsn = pegawaiStatus('non-asn-kontrak');

    try {
        simpanPangkat($nonAsn, Golongan::first(), '2020-01-01');
        $this->fail('seharusnya ditolak');
    } catch (ValidationException $e) {
        expect($e->errors()['golongan_id'][0])->toBe('Pegawai Non-ASN tidak memiliki riwayat pangkat ASN.');
    }
});

test('golongan pegawai mengikuti riwayat dengan tmt terbaru', function () {
    $pegawai = pegawaiStatus('pns');

    simpanPangkat($pegawai, gol('pns', 'III/a'), '2018-04-01');
    simpanPangkat($pegawai, gol('pns', 'III/c'), '2022-04-01');
    simpanPangkat($pegawai, gol('pns', 'III/b'), '2020-04-01');

    expect($pegawai->fresh()->golongan_id)->toBe(gol('pns', 'III/c')->id)
        ->and(RiwayatPangkat::where('pegawai_id', $pegawai->id)->where('is_terkini', true)->count())->toBe(1);
});

test('masa kerja bulan di luar 0-11 ditolak', function () {
    expect(fn () => simpanPangkat(pegawaiStatus('pns'), gol('pns', 'III/a'), '2020-01-01', ['masa_kerja_bulan' => 12]))
        ->toThrow(ValidationException::class);
});

test('gaji pokok tersimpan persis sebagai decimal', function () {
    $pegawai = pegawaiStatus('pns');

    $kgb = app(SimpanRiwayatKgb::class)->handle($pegawai, [
        'tmt' => '2024-01-01', 'nomor_sk' => 'KGB/1', 'gaji_pokok' => '4567800.50', 'golongan_id' => gol('pns', 'III/c')->id,
        'masa_kerja_tahun' => 9, 'masa_kerja_bulan' => 2,
    ]);

    expect($kgb->fresh()->gaji_pokok)->toBe('4567800.50')
        ->and($kgb->fresh()->masaKerjaLabel())->toBe('9 th 2 bl')
        ->and($kgb->is_terkini)->toBeTrue();
});

test('kgb non asn ditolak dan golongan beda jenis ditolak', function () {
    expect(fn () => app(SimpanRiwayatKgb::class)->handle(pegawaiStatus('non-asn-tetap-blu'), ['tmt' => '2024-01-01', 'nomor_sk' => 'K']))
        ->toThrow(ValidationException::class)
        ->and(fn () => app(SimpanRiwayatKgb::class)->handle(pegawaiStatus('pns'), ['tmt' => '2024-01-01', 'nomor_sk' => 'K', 'golongan_id' => gol('pppk', 'V')->id]))
        ->toThrow(ValidationException::class);
});

test('kgb terkini mengikuti tmt terbaru', function () {
    $pegawai = pegawaiStatus('pns');
    $baru = app(SimpanRiwayatKgb::class)->handle($pegawai, ['tmt' => '2024-01-01', 'nomor_sk' => 'K2']);
    app(SimpanRiwayatKgb::class)->handle($pegawai, ['tmt' => '2022-01-01', 'nomor_sk' => 'K1']);

    expect(RiwayatKgb::where('pegawai_id', $pegawai->id)->where('is_terkini', true)->value('id'))->toBe($baru->id);
});

test('admin-prodi tidak dapat membuat riwayat pangkat tetapi admin-kepegawaian dapat', function () {
    $prodi = Prodi::factory()->create();
    $pegawai = pegawaiStatus('pns');
    $pegawai->update(['prodi_id' => $prodi->id]);

    $adminProdi = aktorPangkat(Peran::AdminProdi, $prodi);
    expect($adminProdi->can('create', RiwayatPangkat::class))->toBeFalse()
        ->and($adminProdi->can('create', RiwayatKgb::class))->toBeFalse()
        ->and($adminProdi->can('viewAny', RiwayatPangkat::class))->toBeTrue()
        ->and(aktorPangkat(Peran::AdminKepegawaian)->can('create', RiwayatPangkat::class))->toBeTrue();
});

test('relation manager pangkat dan kgb menyimpan lewat form', function () {
    $pegawai = pegawaiStatus('pns');
    $this->actingAs(aktorPangkat(Peran::AdminKepegawaian));

    Livewire::test(PangkatRelationManager::class, ['ownerRecord' => $pegawai, 'pageClass' => EditPegawai::class])
        ->callAction(TestAction::make('create')->table(), [
            'golongan_id' => gol('pns', 'III/c')->id, 'tmt' => '2024-04-01', 'jenis_kenaikan' => 'reguler', 'nomor_sk' => 'SK/2024',
        ])->assertHasNoFormErrors();

    Livewire::test(KgbRelationManager::class, ['ownerRecord' => $pegawai, 'pageClass' => EditPegawai::class])
        ->callAction(TestAction::make('create')->table(), ['tmt' => '2024-04-01', 'nomor_sk' => 'KGB/2024', 'gaji_pokok' => '5000000.00'])
        ->assertHasNoFormErrors();

    expect($pegawai->fresh()->golongan_id)->toBe(gol('pns', 'III/c')->id)->and(RiwayatKgb::count())->toBe(1);
});

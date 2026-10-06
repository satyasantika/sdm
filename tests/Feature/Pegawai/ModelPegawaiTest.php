<?php

use App\Actions\Pegawai\HitungTanggalPensiun;
use App\Actions\Pegawai\UbahStatusAktifPegawai;
use App\Enums\StatusAktifPegawai;
use App\Exceptions\StatusTidakDiizinkan;
use App\Models\Aktivitas;
use App\Models\Pegawai;
use App\Models\RiwayatStatusPegawai;
use App\Models\User;
use App\Support\HashIdentitas;
use App\Support\Konfigurasi;
use Carbon\CarbonImmutable;
use Database\Seeders\KonfigurasiSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->seed(KonfigurasiSeeder::class);
    Cache::flush();
});

test('nik tersimpan terenkripsi tetapi terbaca polos lewat model', function () {
    $pegawai = Pegawai::factory()->create(['nik' => '3278010101900001']);

    $mentah = DB::table('pegawai')->where('id', $pegawai->id)->value('nik');

    expect($mentah)->not->toBe('3278010101900001')->and($mentah)->toStartWith('eyJ')
        ->and($pegawai->fresh()->nik)->toBe('3278010101900001');
});

test('nik dinormalisasi menjadi digit dan hash terisi', function () {
    $pegawai = Pegawai::factory()->create(['nik' => '3278 0101-0190 0001']);

    expect($pegawai->fresh()->nik)->toBe('3278010101900001')
        ->and($pegawai->fresh()->nik_hash)->toBe(HashIdentitas::nik('3278010101900001'))
        ->and(strlen($pegawai->nik_hash))->toBe(64);
});

test('nik ganda melempar pelanggaran unik', function () {
    Pegawai::factory()->create(['nik' => '3278010101900001']);

    expect(fn () => Pegawai::factory()->create(['nik' => '3278010101900001']))->toThrow(QueryException::class);
});

test('tanggal pensiun dosen mengikuti bup dan pembulatan konfigurasi', function () {
    $dosen = Pegawai::factory()->dosen()->create(['tanggal_lahir' => '1965-03-15']);

    expect($dosen->tanggal_pensiun->toDateString())->toBe('2030-04-01');
});

test('tanggal pensiun profesor memakai bup profesor', function () {
    $profesor = Pegawai::factory()->profesor()->create(['tanggal_lahir' => '1965-03-15']);

    expect($profesor->tanggal_pensiun->toDateString())->toBe('2035-04-01');
});

test('tanggal pensiun tendik memakai bup tendik', function () {
    $tendik = Pegawai::factory()->tendik()->create(['tanggal_lahir' => '1970-08-20']);

    expect($tendik->tanggal_pensiun->toDateString())->toBe('2028-09-01');
});

test('pembulatan tepat dan akhir bulan serta perubahan bup terbaca dari konfigurasi', function () {
    $dosen = Pegawai::factory()->dosen()->make(['tanggal_lahir' => '1965-03-15']);

    Konfigurasi::set('pembulatan_tmt_pensiun', 'tepat');
    expect(app(HitungTanggalPensiun::class)->handle($dosen)->toDateString())->toBe('2030-03-15');

    Konfigurasi::set('pembulatan_tmt_pensiun', 'akhir_bulan');
    expect(app(HitungTanggalPensiun::class)->handle($dosen)->toDateString())->toBe('2030-03-31');

    Konfigurasi::set('bup_dosen', 67);
    expect(app(HitungTanggalPensiun::class)->handle($dosen)->toDateString())->toBe('2032-03-31');
});

test('tanggal pensiun dihitung ulang saat tanggal lahir berubah', function () {
    $dosen = Pegawai::factory()->dosen()->create(['tanggal_lahir' => '1965-03-15']);

    $dosen->update(['tanggal_lahir' => '1970-01-10']);

    expect($dosen->fresh()->tanggal_pensiun->toDateString())->toBe('2035-02-01');
});

test('ubah status menolak pensiun ke aktif', function () {
    $pegawai = Pegawai::factory()->create(['status_aktif' => StatusAktifPegawai::Pensiun]);
    $admin = User::factory()->create();

    expect(fn () => app(UbahStatusAktifPegawai::class)->handle($pegawai, StatusAktifPegawai::Aktif, CarbonImmutable::today(), null, null, $admin))
        ->toThrow(StatusTidakDiizinkan::class);
});

test('ubah status aktif ke tugas belajar mencatat riwayat', function () {
    $pegawai = Pegawai::factory()->create();
    $admin = User::factory()->create();

    app(UbahStatusAktifPegawai::class)->handle($pegawai, StatusAktifPegawai::TugasBelajar, CarbonImmutable::parse('2026-09-01'), 'SK/123', 'Studi S3', $admin);

    $riwayat = RiwayatStatusPegawai::where('pegawai_id', $pegawai->id)->first();
    expect($pegawai->fresh()->status_aktif)->toBe(StatusAktifPegawai::TugasBelajar)
        ->and($riwayat->dari_status)->toBe(StatusAktifPegawai::Aktif)
        ->and($riwayat->ke_status)->toBe(StatusAktifPegawai::TugasBelajar)
        ->and($riwayat->nomor_sk)->toBe('SK/123')
        ->and($riwayat->oleh_user_id)->toBe($admin->id);
});

test('toarray tidak memuat data sensitif', function () {
    $array = Pegawai::factory()->create()->toArray();

    expect($array)->not->toHaveKeys(['nik', 'npwp', 'nomor_rekening', 'nik_hash']);
});

test('activity log tidak memuat nilai nik npwp rekening', function () {
    $pegawai = Pegawai::factory()->create(['nik' => '3278010101900001', 'npwp' => '12.345.678.9-012.345', 'nomor_rekening' => '1234567890']);
    $pegawai->update(['nik' => '3278010101900002', 'nama' => 'Nama Baru']);

    $semua = Aktivitas::all()->map(fn ($a) => json_encode([$a->attribute_changes, $a->properties]))->implode(' ');

    expect($semua)->not->toContain('3278010101900001')->not->toContain('3278010101900002')
        ->not->toContain('12.345.678.9')->not->toContain('1234567890')
        ->and($semua)->toContain('Nama Baru')->toContain('kolom_sensitif_diubah');
});

test('nama bergelar dan scope dosen tetap', function () {
    $dosen = Pegawai::factory()->create(['gelar_depan' => 'Dr.', 'nama' => 'Siti Aminah', 'gelar_belakang' => 'M.Pd.']);
    Pegawai::factory()->create(['status_aktif' => StatusAktifPegawai::Pensiun]);
    Pegawai::factory()->tendik()->create();

    expect($dosen->nama_bergelar)->toBe('Dr. Siti Aminah, M.Pd.')
        ->and(Pegawai::dosenTetap()->count())->toBe(1)
        ->and(Pegawai::tendik()->count())->toBe(1);
});

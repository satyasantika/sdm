<?php

use App\Actions\Riwayat\SimpanSertifikasi;
use App\Enums\StatusBerlaku;
use App\Models\JenisSertifikasi;
use App\Models\Pegawai;
use App\Models\Sertifikasi;
use App\Support\Konfigurasi;
use Carbon\Carbon;
use Database\Seeders\KonfigurasiSeeder;
use Database\Seeders\MasterPendukungSeeder;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->seed(KonfigurasiSeeder::class);
    $this->seed(MasterPendukungSeeder::class);
    Carbon::setTestNow('2026-10-06');
});

afterEach(fn () => Carbon::setTestNow());

function simpanSertifikat(Pegawai $pegawai, string $kodeJenis, array $tambahan = []): Sertifikasi
{
    return app(SimpanSertifikasi::class)->handle($pegawai, array_merge([
        'jenis_sertifikasi_id' => JenisSertifikasi::firstWhere('kode', $kodeJenis)->id, 'nama' => 'Sertifikat Uji',
    ], $tambahan));
}

test('status berlaku dihitung dari tanggal kedaluwarsa untuk empat kasus', function () {
    expect(StatusBerlaku::dariTanggal(null))->toBe(StatusBerlaku::TanpaBatas)
        ->and(StatusBerlaku::dariTanggal(Carbon::parse('2026-10-05')))->toBe(StatusBerlaku::Kedaluwarsa)
        ->and(StatusBerlaku::dariTanggal(Carbon::parse('2026-10-06')))->toBe(StatusBerlaku::SegeraBerakhir)
        ->and(StatusBerlaku::dariTanggal(Carbon::parse('2027-01-04')))->toBe(StatusBerlaku::SegeraBerakhir)
        ->and(StatusBerlaku::dariTanggal(Carbon::parse('2027-01-05')))->toBe(StatusBerlaku::Berlaku);
});

test('ambang segera berakhir mengikuti konfigurasi', function () {
    Konfigurasi::set('ambang_segera_berakhir_hari', 10);

    expect(StatusBerlaku::dariTanggal(Carbon::parse('2026-10-20')))->toBe(StatusBerlaku::Berlaku)
        ->and(StatusBerlaku::dariTanggal(Carbon::parse('2026-10-16')))->toBe(StatusBerlaku::SegeraBerakhir);
});

test('status berlaku tersimpan otomatis saat disimpan', function () {
    $pegawai = Pegawai::factory()->create();

    $s = simpanSertifikat($pegawai, 'kompetensi', ['tanggal_kedaluwarsa' => '2026-12-01']);

    expect($s->status_berlaku)->toBe(StatusBerlaku::SegeraBerakhir);
    $s->update(['tanggal_kedaluwarsa' => '2030-01-01']);
    expect($s->fresh()->status_berlaku)->toBe(StatusBerlaku::Berlaku);
});

test('sertifikat kompetensi tanpa tanggal kedaluwarsa ditolak', function () {
    expect(fn () => simpanSertifikat(Pegawai::factory()->create(), 'kompetensi'))->toThrow(ValidationException::class);
});

test('serdos kedua ditolak dan punya serdos benar', function () {
    $pegawai = Pegawai::factory()->create();
    expect($pegawai->punyaSerdos())->toBeFalse();

    $pertama = simpanSertifikat($pegawai, 'serdos', ['nomor_registrasi' => '123']);
    expect($pegawai->punyaSerdos())->toBeTrue()
        ->and(fn () => simpanSertifikat($pegawai, 'serdos'))->toThrow(ValidationException::class);

    // mengedit serdos yang sama tetap diperbolehkan
    app(SimpanSertifikasi::class)->handle($pegawai, ['jenis_sertifikasi_id' => $pertama->jenis_sertifikasi_id, 'nama' => 'Diubah'], $pertama);
    expect($pertama->fresh()->nama)->toBe('Diubah');

    $pertama->delete();
    expect($pegawai->punyaSerdos())->toBeFalse();
    simpanSertifikat($pegawai, 'serdos');
    expect($pegawai->punyaSerdos())->toBeTrue();
});

test('serdos pegawai lain tidak mengganggu', function () {
    simpanSertifikat(Pegawai::factory()->create(), 'serdos');

    expect(simpanSertifikat(Pegawai::factory()->create(), 'serdos'))->toBeInstanceOf(Sertifikasi::class);
});

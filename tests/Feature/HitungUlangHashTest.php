<?php

use App\Models\Pegawai;
use App\Support\HashIdentitas;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

function rotasiKunci(): string
{
    $lama = config('app.key');
    config(['app.key' => 'base64:'.base64_encode(random_bytes(32)), 'app.previous_keys' => [$lama]]);
    app()->forgetInstance('encrypter');
    Crypt::clearResolvedInstance('encrypter');

    return $lama;
}

test('hitung ulang hash mengikuti kunci baru dan nik tetap terbaca', function () {
    $pegawai = Pegawai::factory()->create(['nik' => '3278010101900001']);
    $hashLama = DB::table('pegawai')->where('id', $pegawai->id)->value('nik_hash');
    $terhapus = Pegawai::factory()->create(['nik' => '3278010101900002']);
    $terhapus->delete();

    rotasiKunci();

    $this->artisan('sdm:hitung-ulang-hash')->expectsOutputToContain('2 pegawai')->assertSuccessful();

    $baru = DB::table('pegawai')->where('id', $pegawai->id)->value('nik_hash');
    expect($baru)->not->toBe($hashLama)->and($baru)->toBe(HashIdentitas::nik('3278010101900001'))
        ->and(Pegawai::find($pegawai->id)->nik)->toBe('3278010101900001')
        ->and(DB::table('pegawai')->where('id', $terhapus->id)->value('nik_hash'))->toBe(HashIdentitas::nik('3278010101900002'));
});

test('hitung ulang hash gagal jelas bila nik tidak dapat didekripsi', function () {
    Pegawai::factory()->create(['nik' => '3278010101900001']);
    config(['app.key' => 'base64:'.base64_encode(random_bytes(32)), 'app.previous_keys' => []]);
    app()->forgetInstance('encrypter');
    Crypt::clearResolvedInstance('encrypter');

    $this->artisan('sdm:hitung-ulang-hash')->expectsOutputToContain('tidak dapat didekripsi')->assertFailed();
});

test('hitung ulang hash tanpa perubahan kunci menghasilkan nilai sama', function () {
    $pegawai = Pegawai::factory()->create(['nik' => '3278010101900003']);
    $hash = DB::table('pegawai')->where('id', $pegawai->id)->value('nik_hash');

    Artisan::call('sdm:hitung-ulang-hash');

    expect(DB::table('pegawai')->where('id', $pegawai->id)->value('nik_hash'))->toBe($hash);
});

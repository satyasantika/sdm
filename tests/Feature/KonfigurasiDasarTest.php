<?php

use Illuminate\Support\Facades\Validator;

test('zona waktu aplikasi adalah asia jakarta', function () {
    expect(config('app.timezone'))->toBe('Asia/Jakarta')
        ->and(now()->getTimezone()->getName())->toBe('Asia/Jakarta');
});

test('locale aplikasi adalah indonesia', function () {
    expect(app()->getLocale())->toBe('id')
        ->and(config('app.faker_locale'))->toBe('id_ID');
});

test('pesan validasi berbahasa indonesia', function () {
    $pesan = Validator::make([], ['nama' => 'required'])->errors()->first('nama');

    expect($pesan)->toBe('Isian nama wajib diisi.');
});

test('antrean database memakai dua koneksi dengan retry_after di atas timeout worker', function () {
    $ringan = config('queue.connections.database');
    $berat = config('queue.connections.database-berat');

    expect($ringan['driver'])->toBe('database')
        ->and($ringan['after_commit'])->toBeTrue()
        ->and($ringan['retry_after'])->toBeGreaterThan(60)
        ->and($berat['driver'])->toBe('database')
        ->and($berat['queue'])->toBe('berat')
        ->and($berat['after_commit'])->toBeTrue()
        ->and($berat['retry_after'])->toBeGreaterThan(900);
});

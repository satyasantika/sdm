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

test('redis memakai database terpisah untuk cache dan antrean', function () {
    expect((string) config('database.redis.cache.database'))->toBe('1')
        ->and((string) config('database.redis.queue.database'))->toBe('2')
        ->and(config('queue.connections.redis.connection'))->toBe('queue')
        ->and(config('queue.connections.redis.retry_after'))->toBe(660);
});

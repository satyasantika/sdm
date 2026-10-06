<?php

test('dasbor horizon dapat diakses di lingkungan local', function () {
    $this->app['env'] = 'local';

    $this->get('/horizon')->assertOk();
});

test('lingkungan local memiliki lima supervisor bernama', function () {
    $supervisor = config('horizon.environments.local');

    expect(array_keys($supervisor))->toBe([
        'supervisor-default', 'supervisor-impor', 'supervisor-ekspor', 'supervisor-notifikasi', 'supervisor-tautan',
    ])
        ->and($supervisor['supervisor-notifikasi']['queue'])->toBe(['notifikasi'])
        ->and(config('queue.connections.redis.retry_after'))->toBe(660);
});

test('snapshot horizon terjadwal setiap lima menit', function () {
    $this->artisan('schedule:list')->expectsOutputToContain('horizon:snapshot')->assertSuccessful();
});

test('setiap supervisor di semua lingkungan menyebut koneksi dan antrean', function () {
    foreach (config('horizon.environments') as $lingkungan => $supervisors) {
        expect(array_keys($supervisors))->toHaveCount(5);

        foreach ($supervisors as $nama => $opsi) {
            expect($opsi)->toHaveKeys(['connection', 'queue'], "{$lingkungan}.{$nama}");
        }
    }
});
